<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork;

use PBergman\SystemV\IPC\Messages\ServiceException as MessagesException;
use PBergman\SystemV\IPC\Semaphore\Service as SemaphoreService;
use PBergman\SystemV\IPC\Messages\Receiver;
use PBergman\SystemV\IPC\Messages\Service as MessagesService;
use PBergman\Fork\Work\AbstractWork;
use PBergman\Fork\Helpers\ErrorHelper as ErrorHandler;
use PBergman\Fork\Helpers\ExitHelper as ExitHandler;
use PBergman\Fork\Helpers\OutputHelper;
use PBergman\Fork\Work\Controller;

class Manager
{
    /** @var int  */
    private $workers = 1;
    /** @var \SplObjectStorage  */
    private $jobs;
    /** @var int  */
    private $state;
    /** @var OutputHelper  */
    private $output;
    /** @var array  */
    private $finishedJobs = array();
    /** @var int */
    private $maxSize = 16384;
    /** @var array  */
    private $pids = array();
    /** @var int  */
    private $tokenSem;
    /** @var int  */
    private $tokenMsg;

    const STATE_CHILD  = 1;
    const STATE_PARENT = 2;

    /**
     * @param bool          $debug      if true will print backtrace for warnings/errors
     * @param OutputHelper  $output
     * @param string        $file       files used to generate tokens
     */
    public function __construct($debug = false, OutputHelper $output = null, $file = __FILE__)
    {
        if (is_null($output)) {
            $this->output = new OutputHelper();
        } else {
            $this->output = $output;
        }

        // Enable custom error handler
        ErrorHandler::enable($this->output, E_ALL | E_STRICT, $debug);

        $this->jobs   = new \SplObjectStorage();
        $this->state  = self::STATE_PARENT;
        $this->pid    = posix_getpid();
        $this->onExit = new ExitHandler();

        $this->tokenSem = ftok($file, 'm');
        $this->tokenMsg = ftok($file, 's');

        $this->setupExit();
    }


    /**
     * main method that spawns children
     * end divides the work with workers
     *
     * @throws \Exception
     */
    public function run()
    {
        $queue = new MessagesService($this->tokenMsg, 0660);
        $sem   = new SemaphoreService($this->tokenSem, $this->workers, 0660, false);

        $this->jobs->rewind();

        while($this->jobs->valid()) {

            /** @var AbstractWork $work */
            $work = $this->jobs->current();
            // For stack reference
            $work->setParentPid(posix_getpid());

            $sem->acquire();
            $this->sync($queue->getReceiver());

            $pid = pcntl_fork();

            switch($pid) {
                case -1:     // @fail/
                    throw new \Exception('Could not fork process');
                    break;
                case 0:     // @child
                    $this->state = self::STATE_CHILD;
                    $this->jobs  = null;
                    $controller  = new Controller($this->output, $queue->getSender(), $sem, $this->onExit);
                    $controller->run($work);
                    break;
                default:    // @parent
                    $this->pids[$pid] = 1;
            }


            $this->jobs->next();
            $this->jobs->detach($work);
        }

        if ($this->state === self::STATE_PARENT) {

            // Wait for children.....
            while(array_sum($this->pids) >= 1) {
                $this->sync($queue->getReceiver());
            }

            ksort($this->finishedJobs);

            while (null !== $job = array_shift($this->finishedJobs)) {
                $this->jobs->attach($job);
            }

            $this->jobs->rewind();
        }

    }
    /**
     * will check if running children are
     * finished if so will update pids var
     *
     * @param   Receiver            $receiver
     * @throws  MessagesException
     */
    private function sync(Receiver $receiver)
    {
        // Make extra loop so we pick up children
        // finished closely after each other
        for($i = 0 ; $i < array_sum($this->pids); $i++) {
            foreach($this->pids as $pid => &$isRunning) {
                if ($isRunning === 1) {
                    if (pcntl_waitpid($pid, $status, WNOHANG | WUNTRACED)) {

                        $received = $receiver->setType($pid)
                                             ->setMaxSize($this->maxSize)
                                             ->pull();

                        if (false === $received->isSuccess()) {
                            trigger_error(sprintf('Failed to receive message, %s(%s)', $receiver->getError(), $receiver->getErrorCode()), E_USER_ERROR);
                        }

                        /** @var AbstractWork $object */
                        $object = $received->getData();

                        if (pcntl_wifstopped($status)) {

                            $object->setExitCode(null)
                                   ->setError(sprintf('Signal: %s caused this child to stop.',  pcntl_wstopsig($status)))
                                   ->isSuccess(false);

                        } elseif(pcntl_wifsignaled($status)) {

                            $object->setExitCode(pcntl_wexitstatus($status))
                                   ->setError(sprintf('Signal: %s caused this child to exit', pcntl_wtermsig($status)))
                                   ->isSuccess(false);

                        } else {

                            $object->setExitCode(pcntl_wexitstatus($status));

                        }

                        $this->finishedJobs[$object->getPid()] = $object;

                        $isRunning = 0;
                    }
                }
            }
        }
    }

    /**
     * get amount of workers
     *
     * @return int
     */
    public function getWorkers()
    {
        return $this->workers;
    }

    /**
     * set amount of workers to precess jobs
     *
     * @param   int $workers
     * @return  $this
     */
    public function setWorkers($workers)
    {
        $this->workers = $workers;
        return $this;
    }

    /**
     * get defined jobs
     *
     * @return array
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * set all jobs at once
     *
     * @param   array $jobs
     * @return  $this
     */
    public function setJobs(array $jobs)
    {
        $this->jobs->removeAll($this->jobs);

        foreach ($jobs as $job) {
            $this->addJob($job);
        }

        return $this;
    }

    /**
     * add job to stack
     *
     * @param   AbstractWork $job
     * @return  $this
     */
    public function addJob(AbstractWork $job)
    {
        $this->jobs->attach($job);

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxSize()
    {
        return $this->maxSize;
    }

    /**
     * @param   int     $maxSize
     * @return  $this;
     */
    public function setMaxSize($maxSize)
    {
        $this->maxSize = $maxSize;
        return $this;
    }

    /**
     * exit helper, that will kill children on error
     * and removes semaphore and message queue
     */
    private function setupExit()
    {
        $onExit = new ExitHandler();
        $onExit->addCallback(function($state, $pids, OutputHelper $output){

            if ($state === Manager::STATE_PARENT) {
                if (null !== $error = error_get_last()) {
                    if ($error['type'] & (E_ERROR | E_USER_ERROR)) {
                        foreach($pids as $pid => $isRunning) {
                            if ($isRunning) {
                                $output->debug(sprintf('Killing child: %s', $pid));
                                posix_kill($pid, SIGKILL);
                            }
                        }
                    }
                }

                $queue = new MessagesService($this->tokenMsg, 0660);
                $sem   = new SemaphoreService($this->tokenSem, $this->workers, 0660, false);

                $queue->remove();
                $sem->remove();

            }

        }, array(&$this->state, &$this->pids, $this->output));

        return $this;

    }
}