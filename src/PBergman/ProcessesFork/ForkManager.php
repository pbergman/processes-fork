<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\ProcessesFork;

use PBergman\Semaphore\MessageQueue;
use PBergman\Semaphore\Semaphore;
use PBergman\Semaphore\SharedMemory;

/**
 * Class ForkManager
 *
 * @property    \SplObjectStorage   jobs
 *
 * @package     PBergman\ProcessesFork
 */
class ForkManager
{
    private $workers = 1;
    private $jobs;
    private $state;
    private $exists = array();
    private $output;
    const STATE_CHILD  = 1;
    const STATE_PARENT = 2;

    const QUEUE_STATE_TODO     = 1;
    const QUEUE_STATE_FINISHED = 2;

    public function __construct()
    {
        $this->output = new OutputHandler();
        $this->jobs   = new \SplObjectStorage();
        $this->state  = self::STATE_PARENT;
    }

    /**
     * main method that spawns children
     * end divides the work with workers
     *
     * @throws \Exception
     */
    public function run()
    {
        $queue = new MessageQueue(ftok(__FILE__, 'm'), 0660);
        $pids  = array();
        $max   = $this->jobs->count();

        $this->setJobsToQueue($queue);

        for($i = 0; $i < $max; ++$i) {

            $this->wait($pids, $this->workers);

            $pid = pcntl_fork();

            switch($pid) {
                case -1:     // @fail/
                    throw new \Exception('Could not fork process');
                    break;
                case 0:     // @child
                    $this->state = self::STATE_CHILD;
                    $this->startChild($queue, $i);
                    break;
                default:    // @parent
                    $pids[$pid] = 1;
            }

        }


        if ($this->state === self::STATE_PARENT) {

            $this->wait($pids);

            /** @var ForkJobInterface $object */
            while($data = $queue->receive(self::QUEUE_STATE_FINISHED, $msgtype, 10000, $object, true, MSG_IPC_NOWAIT, $error)) {
                    $object->setExitCode($this->exists[$object->getPid()]);
                    $this->jobs->attach($object);
            }

           $queue->remove();

        }

    }


    private function startChild(MessageQueue $queue, $id)
    {
        /** Set custom error handling */
        $eh = new ErrorHandler($this->output);

        $this->output->debug('Spawning child', posix_getpid(), OutputHandler::PROCESS_CHILD);

        /** @var ForkJobInterface $object */
        $queue->receive(self::QUEUE_STATE_TODO, $msgtype, 10000, $object);

        /**
         * Set some exit callback for when the child exists
         */
        $ex = new ExitRegister();
        $ex->addCallback(function(ForkJobInterface &$object, MessageQueue $queue){
            if (null !== $error = error_get_last()) {
                if ($error['type'] === E_ERROR) {
                    $object->setSuccess(false)
                           ->setExitCode(255)
                           ->setPid(posix_getpid())
                           ->setError(sprintf("Fatal error: %s on line %s in file %s", $error['message'], $error['line'], $error['file']));
                }
            }

            $queue->send($object, self::QUEUE_STATE_FINISHED);

        }, array(&$object, &$queue));


        try{
            $object->execute();
            $object->setPid(posix_getpid())
                   ->postExecute();

            exit(0);
        } catch(\Exception $e) {
            $object->setSuccess(false)
                   ->setError($e->getMessage())
                   ->setPid(posix_getpid())
                   ->postExecute();

            exit($e->getCode());
        }

    }

    /**
     * will get objects from object storage and place
     * them in the message queue so the child process
     * can pick them up when the are spawned
     *
     * @param MessageQueue $queue
     */
    private function setJobsToQueue(MessageQueue $queue)
    {
        $this->jobs->rewind();

        while($this->jobs->valid()) {
            /** @var ForkJobInterface $ob */
            $id = $this->jobs->key();
            $ob = $this->jobs->current();
            $ob->setId($id);
            $queue->send($ob, self::QUEUE_STATE_TODO);
            $this->jobs->next();
        }

        $this->jobs->removeAll($this->jobs);
    }

    /**
     *  will wait if running pids is more than
     *  given limit argument
     *
     *  pids array should be like:
     *
     *  array(
     *      [1234] => 1,
     *      [1235] => 0,
     *  )
     *
     *  pid as key and running status as value
     *
     * @param array $pids   stack of child process pids
     * @param int   $limit  the limit of the max workers
     */
    private function wait(&$pids, $limit = 1)
    {

        while(array_sum($pids) >= $limit) {

            if($pid = pcntl_waitpid(0, $status)) {
                $this->exists[$pid] = pcntl_wexitstatus($status);
                $this->output->debug(sprintf('Child finished with exit code %s', pcntl_wexitstatus($status)), $pid);
            }

            $pids[$pid] = 0;
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
     * @param   ForkJobInterface $job
     * @return  $this
     */
    public function addJob(ForkJobInterface $job)
    {
        $this->jobs->attach($job);

        return $this;
    }
}
