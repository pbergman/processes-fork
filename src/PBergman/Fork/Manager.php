<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork;

use PBergman\Fork\Work\Controller;
use PBergman\SystemV\IPC\Semaphore\Service as SemaphoreService;
use PBergman\SystemV\IPC\Messages\Service as MessagesService;
use PBergman\Fork\Work\AbstractWork;

class Manager
{
    /** @var int  */
    private $workers = 1;
    /** @var \SplObjectStorage  */
    private $jobs;
    /** @var int  */
    private $state;
    /** @var \PBergman\Fork\Helpers\OutputHelper  */
    private $output;
    /** @var array  */
    private $finishedJobs = array();

    const STATE_CHILD  = 1;
    const STATE_PARENT = 2;

    const QUEUE_STATE_TODO     = 1;
    const QUEUE_STATE_FINISHED = 2;

    public function __construct()
    {
        $this->output = new Helpers\OutputHelper();
        $this->jobs   = new \SplObjectStorage();
        $this->state  = self::STATE_PARENT;
        $this->pid    = posix_getpid();
    }


    /**
     * main method that spawns children
     * end divides the work with workers
     *
     * @throws \Exception
     */
    public function run()
    {
        $queue = new MessagesService(ftok(__FILE__, 'm'), 0660);
        $pids  = array();
        $sem   = new SemaphoreService(ftok(__FILE__, 's'), $this->workers, 0660, false);

        $this->jobs->rewind();

        while($this->jobs->valid()) {

            /** @var AbstractWork $ob */
            $work = $this->jobs->current();
            // For stack reference
            $work->setParentPid(posix_getpid());

            $sem->acquire();
            $this->checkRunningChildren($pids, $queue);

            $pid = pcntl_fork();

            switch($pid) {
                case -1:     // @fail/
                    throw new \Exception('Could not fork process');
                    break;
                case 0:     // @child
                    $this->state = self::STATE_CHILD;
                    $this->jobs  = null;
                    $controller  = new Controller($this->output, $queue, $sem);
                    $controller->run($work);
                    break;
                default:    // @parent
                    $pids[$pid] = 1;
            }


            $this->jobs->next();
            $this->jobs->detach($work);
        }

        if ($this->state === self::STATE_PARENT) {

            // Wait for children.....
            while(array_sum($pids) >= 1) {
                $this->checkRunningChildren($pids, $queue);
            }

            ksort($this->finishedJobs);

            foreach ($this->finishedJobs as  $job) {
                $this->jobs->attach($job);
            }

            $this->jobs->rewind();

            // Cleanup!
            $queue->remove();
            $sem->remove();
        }

    }
    /**
     * will check if running children are
     * finished if so will update pids var
     *
     *  pids array should be like:
     *
     *  array(
     *      [1234] => 1,    // child with pid 1234 is running
     *      [1235] => 0,    // child with pid 1235 is not running
     *  )
     *
     * @param array             $pids
     * @param MessagesService   $queue
     */
    private function checkRunningChildren(&$pids, MessagesService $queue)
    {
        // Make extra loop so we pick up children
        // finished closely after each other
        for($i =0 ; $i < array_sum($pids); $i++) {
            foreach($pids as $pid => &$isRunning) {
                if ($isRunning === 1) {
                    if (pcntl_waitpid($pid, $status, WNOHANG | WUNTRACED)) {

                        /** @var AbstractWork $object */
                        $queue->receive($pid, $msgtype, 20480, $object);

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
}