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

    private $workers = 1;
    private $jobs;
    private $state;
    private $output;
    private $stats = array();

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
        $this->pids   = array();
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
        $max   = $this->jobs->count();
        $sem   = new SemaphoreService(ftok(__FILE__, 's'), $this->workers, 0660, false);
        $this->setJobsToQueue($queue);

//        pcntl_signal(SIGCHLD, function($signal){
//            switch($signal) {
//                case SIGCHLD:
//                    while ($pid = pcntl_waitpid(0, $status) != -1) {
//                        $status = pcntl_wexitstatus($status);
//                        echo "Child $pid completed\n";
//                    }
//                    exit;
//            }
//        });


        for($i = 0; $i < $max; ++$i) {

           $sem->acquire();

           $this->checkRunningChildren($pids);

            $pid = pcntl_fork();

            switch($pid) {
                case -1:     // @fail/
                    throw new \Exception('Could not fork process');
                    break;
                case 0:     // @child
                    $this->state = self::STATE_CHILD;

                    $this->stats[posix_getpid()] = array(
                        'start_time' => microtime(true),
                        'exit_code'  => 0,
                        'id'         => $i,
                    );

                    $controller = new Controller($this->output, $queue, $sem, $this->stats);
                    $controller->run($this->pid);

                    break;
                default:    // @parent
                    $this->pids[$pid] = 1;
                    $pids[$pid] = 1;
            }

        }


        if ($this->state === self::STATE_PARENT) {

            // Wait for children.....
            while(array_sum($pids) >= 1) {
                $this->checkRunningChildren($pids);
            }

            /**
             * fetch job back from message queue, add
             * exit code and put back into object storage
             *
             * @var AbstractWork $object
             */
            while($data = $queue->receive(self::QUEUE_STATE_FINISHED, $msgtype, 10000, $object, true, MSG_IPC_NOWAIT, $error)) {
                $object->setExitCode($this->stats[$object->getPid()]['exit_code']);
                $this->jobs->attach($object);
            }

            // Cleanup!
            $queue->remove();
            $sem->remove();
        }

    }

    /**
     * will get objects from object storage and place
     * them in the message queue so the child process
     * can pick them up when the are spawned
     *
     * @param MessagesService $queue
     */
    private function setJobsToQueue(MessagesService $queue)
    {
        $this->jobs->rewind();

        while($this->jobs->valid()) {
            /** @var AbstractWork $ob */
            $id = $this->jobs->key();
            $ob = $this->jobs->current();
            $ob->setId($id);
            $queue->send($ob, self::QUEUE_STATE_TODO);
            $this->jobs->next();
        }

        $this->jobs->removeAll($this->jobs);
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
     * @param $pids
     */
    private function checkRunningChildren(&$pids)
    {
        // Make extra loop so we pick up children
        // finished closely after each other
        for($i =0 ; $i < array_sum($pids); $i++) {
            foreach($pids as $pid => &$isRunning) {
                if ($isRunning === 1) {
                    if (pcntl_waitpid($pid, $status, WNOHANG | WUNTRACED)) {
                        $isRunning = 0;
                        $exit = pcntl_wexitstatus($status);
                        $this->stats[$pid]['exit_code'] = $exit;
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