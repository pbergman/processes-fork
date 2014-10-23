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
 * @package PBergman\Fork
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
    /** @var  Container|\PBergman\Fork\Helper\IdentifierHelper[]|\PBergman\Fork\Helper\ExitHelper[]|\PBergman\Fork\Helper\SignalHelper[]|\PBergman\SystemV\IPC\Messages\Service[]|\PBergman\SystemV\IPC\Semaphore\Service[] */
    private $container;
    /** @var  callable */
    private $postForkChild;
    /** @var  callable */
    private $postForkParent;
    /** @var int retries befor trigger error when reading message queue */
    private $receiveRetries = 4;
    /** @var array|\PBergman\SystemV\IPC\Messages\Service[] $queues */
    private $queues;

    // Some const message queue
    const SEND_CHILD  = 1;
    const SEND_PARENT = 2;

    // Some const for remove method
    const CLEAR_SEMAPHORE       = 1;
    const CLEAR_MESSAGE_QUEUE   = 2;
    const CLEAR_JOBS            = 4;
    const CLEAR_FINISHED_JOBS   = 8;
    const CLEAR_ALL             = 15;

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

        $this->container['sem.conf.token'] = ftok($file, 's');
        $this->container['fm.max_size']    = $this->maxSize;

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
        $this->container['helper.exit']->register(function(){
            // Make sure children die on (error) exit and cleanup
            if (ErrorHandler::hasError(E_ERROR | E_USER_ERROR)) {
                $this->killChildren();
            }
            $this->cleanup(self::CLEAR_ALL);
        });

        /**
         * Setup signal handler && Trap Ctrl-C && Ctrl-/
         */
        $this->container['helper.signal']->register(array(SIGINT, SIGQUIT), function($signal) {
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
     * @return \PBergman\Fork\ForkManager
     * @throws \Exception
     */
    public function run()
    {
        $this->container['sem.conf.workers'] = $this->workers;
        $this->jobs->rewind();
        /** @var \PBergman\SystemV\IPC\Semaphore\Service $semaphore */
        $semaphore  = $this->container['semaphore'];
        /** @var \PBergman\Fork\Helper\IdentifierHelper $identifier */
        $identifier = $this->container['helper.identifier'];
        while($this->jobs->valid()) {
            /** @var AbstractWork $work */
            $work = $this->jobs->current();
            $work->setParentPid($identifier->getParentPid());
            /** Check for finished children */
            $this->sync();
            /** Acquire semaphore lock */
            $semaphore->acquire();
            /** @var \PBergman\SystemV\IPC\Messages\Service $messageQueue */
            $messageQueue = $this->getMessageQueue();
            /** @var int $pid */
            $pid = pcntl_fork();

            switch($pid) {
                case -1:     // @fail/
                    throw new \Exception('Could not fork process');
                    break;
                case 0:     // @child
                    $this->jobs  = null;
                    $this->container['instance.semaphore']     = $semaphore;
                    $this->container['instance.message_queue'] = $messageQueue;
                    $this->checkPostForkCallback();
                    $controller  = new Controller($this->container);
                    $controller->run($work);
                    break;
                default:    // @parent
                    $this->checkPostForkCallback();
                    $this->pids[$pid]   = 1;
                    $this->queues[$pid] = $messageQueue;
            }

            $this->jobs->next();
            $this->jobs->detach($work);
        }

        if ($identifier->isParent()) {

            // Wait for children.....
            while(array_sum($this->pids) >= 1) {
                $this->sync();
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
     * @return \PBergman\SystemV\IPC\Messages\Service
     */
    protected function getMessageQueue()
    {
        $token = $this->createId();
        $file  = sprintf('/tmp/%s', $token);
        file_put_contents($file, '');
        $this->container['mess.conf.token'] = ftok($file, 'm');
        unlink($file);
        return $this->container['message_queue'];
    }

    /**
     * Check the post fork callbacks
     */
    protected function checkPostForkCallback()
    {
        $callback = ($this->container['helper.identifier']->isParent()) ? $this->postForkParent : $this->postForkChild;

        if (!is_null($callback ) && is_callable($callback)) {
            $callback($this);
        }
    }

    /**
     * will check if running children are
     * finished if so will update pids var
     *
     * @throws  \PBergman\SystemV\IPC\Messages\ServiceException
     */
    private function sync()
    {
        // finished closely after each other
        foreach($this->pids as $pid => &$isRunning) {
            if ($isRunning > 0) {
                if (pcntl_waitpid($pid, $status, WNOHANG | WUNTRACED)) {

                    $receiver = $this->queues[$pid]->getReceiver();
                    $received = $receiver->setType(self::SEND_CHILD)
                                         ->setMaxSize($this->maxSize)
                                         ->pull();

                    if (false === $received->isSuccess()) {
                        if ($isRunning >= $this->receiveRetries) {
                            trigger_error(sprintf('Failed to receive message after %s retries, %s(%s)', $isRunning, $receiver->getError(), $receiver->getErrorCode()), E_USER_ERROR);
                        } else {
                            $isRunning++;
                        }
                    } else {
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

//                        $sender = $this->queues[$pid]
//                            ->getSender()
//                            ->setData($object)
//                            ->setType(ForkManager::SEND_PARENT)
//                            ->push();
//
//                        if (false === $sender->isSuccess()) {
//                            trigger_error(sprintf('Failed to send message, %s(%s)', $sender->getError(), $sender->getErrorCode()), E_USER_ERROR);
//                        }

                        $this->queues[$pid]->remove();
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
     * @return  \PBergman\Fork\ForkManager
     */
    public function setWorkers($workers)
    {
        $this->workers = $workers;
        return $this;
    }

    /**
     * get defined jobs
     *
     * @return \SplObjectStorage
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * set all jobs at once
     *
     * @param   array $jobs
     * @return  \PBergman\Fork\ForkManager
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
     * @return  \PBergman\Fork\ForkManager
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
     * @return  \PBergman\Fork\ForkManager;
     */
    public function setMaxSize($maxSize)
    {
        $this->maxSize = $this->container['fm.max_size'] = $maxSize;
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
     *
     * @param int $level set level for removing
     *
     * @return \PBergman\Fork\ForkManager
     */
    public function cleanup($level = self::CLEAR_JOBS)
    {
        if ($option = ($level & self::CLEAR_SEMAPHORE)) {
            $this->container['semaphore']->remove();
        }

//        if ($option = ($level & self::CLEAR_MESSAGE_QUEUE)) {
//            $this->container['message_queue']->remove();
//        }

        if ($option = ($level & self::CLEAR_JOBS)) {
            $this->jobs->removeAll($this->jobs);
        }

        if ($option = ($level & self::CLEAR_FINISHED_JOBS)) {
            unset($this->finishedJobs);
        }

        return $this;
    }

    /**
     * set callback that is called after
     * creation of child in child process
     *
     * @param   callable $postFork
     * @return  \PBergman\Fork\ForkManager
     */
    public function setPostForkParent(callable $postFork)
    {
        $this->postForkParent = $postFork;
        return $this;
    }

    /**
     * set callback that is called after
     * creation of child in parent process
     *
     * @param   callable $postFork
     * @return  \PBergman\Fork\ForkManager
     */
    public function setPostForkChild(callable $postFork)
    {
        $this->postForkChild = $postFork;
        return $this;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param   Container $container
     * @return  \PBergman\Fork\ForkManager
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Create a uuid v4
     *
     * @return string
     */
    protected function createId()
    {
        $data    = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}