<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork;

use PBergman\SystemV\IPC\Messages\Receiver;
use PBergman\Fork\Work\AbstractWork;
use PBergman\Fork\Work\Controller;
use PBergman\Fork\Output\OutputInterface;
use PBergman\Fork\Output\LogFormatter;

/**
 * Class Manager
 *
 * @package PBergman\ForkManager
 */
class ForkManager
{
    /** @var int  */
    private $workers = 1;
    /** @var \SplObjectStorage  */
    private $jobs;
    /** @var array  */
    private $finishedJobs = array();
    /** @var int */
    private $maxSize = 16384;
    /** @var array  */
    private $pids = array();
    /** @var Container */
    private $container;

    /**
     * @param Container  $container
     * @param string     $file       files used to generate tokens
     */
    public function __construct(Container $container = null, $file = __FILE__)
    {
        if (is_null($container)) {
            $this->container = new Container();
        } else {
            $this->container = $container;
        }

        $this->container['sem.conf.token']  = ftok($file, 's');
        $this->container['mess.conf.token'] = ftok($file, 'm');

        $this->jobs = new \SplObjectStorage();
        $this->pid  = $this->container['helper.identifier']->getPid();

        $this->initialize();
    }

    /**
     *
     * initialize/setup dependencies
     *
     */
    protected function initialize()
    {
        /**
         * register exit handler
         *
         * @var \PBergman\Fork\Helper\ExitHelper $exit
         */
        $exit = $this->container['helper.exit'];
        $exit->register(function(){
            // Make sure children die on (error) exit and cleanup
            if (ErrorHandler::hasError(E_ERROR | E_USER_ERROR)) {
                $this->killChildren();
            }
            $this->cleanup();
        });

        /**
         * Setup signal handler && Trap Ctrl-C && Ctrl-/
         *
         * @var \PBergman\Fork\Helper\SignalHelper $signal
         */
        $signal = $this->container['helper.signal'];
        $signal->register(array(SIGINT, SIGQUIT), function($signal) {
            if($this->container['helper.identifier']->isParent()) {
                $this->killChildren();
                exit($signal);
            }
        });

        //Enable custom error handler
        ErrorHandler::enable($this->container['output']);

    }

    /**
     * main method that spawns children
     * end divides the work with workers
     *
     * @return $this
     * @throws \Exception
     */
    public function run()
    {
        $this->container['sem.conf.workers'] = $this->workers;

        $this->jobs->rewind();

        /** @var \PBergman\SystemV\IPC\Semaphore\Service $sem */
        $semaphore  = $this->container['semaphore'];
        /** @var \PBergman\SystemV\IPC\Messages\Service  $queue */
        $queue      = $this->container['messages'];
        /** @var \PBergman\Fork\Helper\IdentifierHelper $identifier */
        $identifier = $this->container['helper.identifier'];

        while($this->jobs->valid()) {

            /** @var AbstractWork $work */
            $work = $this->jobs->current();
            $work->setParentPid($identifier->getParentPid());

            $semaphore->acquire();

            $this->sync($queue->getReceiver());

            $pid = pcntl_fork();

            switch($pid) {
                case -1:     // @fail/
                    throw new \Exception('Could not fork process');
                    break;
                case 0:     // @child
                    $this->jobs  = null;
                    $controller  = new Controller($queue->getSender(), $this->container, $semaphore);
                    $controller->run($work);
                    break;
                default:    // @parent
                    $this->pids[$pid] = 1;
            }

            $this->jobs->next();
            $this->jobs->detach($work);
        }

        if ($identifier->isParent()) {

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

        return $this;
    }

    /**
     * will check if running children are
     * finished if so will update pids var
     *
     * @param   Receiver            $receiver
     * @throws  \PBergman\SystemV\IPC\Messages\ServiceException
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
     * helper to kill all running children
     */
    protected function killChildren()
    {
        /** @var OutputInterface $output */
        $output = $this->container['output'];

        foreach($this->pids as $pid => $isRunning) {
            if ($isRunning) {
                posix_kill($pid, SIGKILL);      // Send kill signal
                $output->write((new LogFormatter())->format(sprintf('Send SIGKILL to child(%s)', $pid)));
            }
        }
    }

    /***
     * cleanup for removing resources
     */
    public function cleanup()
    {
        $this->container['semaphore']->remove();
        $this->container['messages']->remove();
        $this->jobs->removeAll($this->jobs);
    }
}