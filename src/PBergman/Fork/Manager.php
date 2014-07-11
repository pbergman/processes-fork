<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork;

use PBergman\SystemV\IPC\Messages\Service as MessagesService;
use PBergman\Fork\Work\WorkInterface;
use PBergman\Fork\Helpers\ErrorHelper  as ErrorHandler;
use PBergman\Fork\Helpers\OutputHelper as OutputHandler;
use PBergman\Fork\Helpers\ExitHelper   as ExitHandler;

class Manager
{
    private $workers = 1;
    private $jobs;
    private $state;
    private $output;
    private $childStats = array();

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

                    $this->childStats[posix_getpid()] = array(
                        'start_time' => microtime(true),
                        'exit_code'  => 0,
                        'id'         => $i,
                    );

                    $this->startChild($queue, $i);
                    break;
                default:    // @parent
                    $pids[$pid] = 1;
            }

        }


        if ($this->state === self::STATE_PARENT) {

            $this->wait($pids);

            /** @var WorkInterface $object */
            while($data = $queue->receive(self::QUEUE_STATE_FINISHED, $msgtype, 10000, $object, true, MSG_IPC_NOWAIT, $error)) {

                $object->setExitCode($this->childStats[$object->getPid()]['exit_code']);

                $this->jobs->attach($object);
            }

            $queue->remove();

        }

    }


    private function startChild(MessagesService $queue, $id)
    {
        // Enable custom error handler
        ErrorHandler::enable($this->output);

        /** @var WorkInterface $object */
        $queue->receive(self::QUEUE_STATE_TODO, $msgtype, 10000, $object);

        $this->output->debug(sprintf('Starting child %s', $object->getName()), posix_getpid(), OutputHandler::PROCESS_CHILD);

        // Set pids
        $object->setParentPid($this->pid)
               ->setPid(posix_getpid());

        // Set some exit callback for when the child exists
        $exitHandler = new ExitHandler();
        $exitHandler->addCallback(function(WorkInterface &$object, MessagesService $queue, $stats){
            if (null !== $error = error_get_last()) {
                if ($error['type'] === E_ERROR) {
                    $object->setSuccess(false)
                        ->setExitCode(255)
                        ->setDuration((microtime(true) - $stats[$object->getPid()]['start_time']))
                        ->setPid(posix_getpid())
                        ->setUsage(memory_get_usage())
                        ->setError(sprintf("Fatal error: %s on line %s in file %s", $error['message'], $error['line'], $error['file']));
                }
            }

            $queue->send($object, self::QUEUE_STATE_FINISHED);

        }, array(&$object, &$queue, $this->childStats));

        // Try execute child process
        try{
            $object->execute();

            $object->setDuration((microtime(true) - $this->childStats[$object->getPid()]['start_time']))
                   ->setUsage(memory_get_usage());

            exit(0);

        } catch(\Exception $e) {

            $object->setSuccess(false)
                   ->setError($e->getMessage())
                   ->setDuration((microtime(true) - $this->childStats[$object->getPid()]['start_time']))
                   ->setUsage(memory_get_usage());

            exit($e->getCode());
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
            /** @var WorkInterface $ob */
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

                $exit = pcntl_wexitstatus($status);
                $this->childStats[$pid]['exit_code'] = $exit;
                $this->output->debug(sprintf('Child finished with exit code %s', $exit), $pid);
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
     * @param   WorkInterface $job
     * @return  $this
     */
    public function addJob(WorkInterface $job)
    {
        $this->jobs->attach($job);

        return $this;
    }
}