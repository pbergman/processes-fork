<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Work;

use PBergman\Fork\Manager;
use \PBergman\SystemV\IPC\Semaphore\Service as SemaphoreService;
use PBergman\SystemV\IPC\Messages\Service as MessagesService;
use PBergman\Fork\Helpers\OutputHelper as OutputHandler;
use PBergman\Fork\Helpers\ErrorHelper  as ErrorHandler;
use PBergman\Fork\Helpers\ExitHelper   as ExitHandler;

/**
 * Class Controller
 *
 * @package PBergman\Fork\Work
 */
class Controller
{
    /** @var OutputHandler  */
    private $output;
    /** @var MessagesService  */
    private $queue;
    /** @var SemaphoreService  */
    private $sem;
    /** @var array  */
    private $stats;

    /**
     * @param OutputHandler     $output
     * @param MessagesService   $queue
     * @param SemaphoreService  $sem
     * @param array             $stats
     */
    public function __construct(OutputHandler $output, MessagesService $queue, SemaphoreService $sem, $stats)
    {
        $this->queue  = $queue;
        $this->output = $output;
        $this->sem    = $sem;
        $this->stats  = $stats;
    }

    public function run($ppid)
    {
        // Enable custom error handler
        ErrorHandler::enable($this->output);//, new SemaphoreService(ftok(__FILE__, 'd'), 1, 0660, 0));
        /** @var AbstractWork $object */
        $this->queue->receive(Manager::QUEUE_STATE_TODO, $msgtype, 10000, $object);

        $this->output->debug(sprintf('Starting: %s', $object->getName()), posix_getpid(), OutputHandler::PROCESS_CHILD);

        // Set pids
        $object->setParentPid($ppid)->setPid(posix_getpid());


        $this->setupExit($object);



        // Try execute child process
        try {
            trigger_error(sprintf('timeout exceeded: %s', 10), E_USER_ERROR);

            $this->setTimeOut($object);

            $object->execute();
            $object->setDuration((microtime(true) - $this->stats[$object->getPid()]['start_time']))
                   ->setUsage(memory_get_usage());

            exit(0);

        } catch(\Exception $e) {

            $object->setSuccess(false)
                ->setError($e->getMessage())
                ->setDuration((microtime(true) - $this->stats[$object->getPid()]['start_time']))
                ->setUsage(memory_get_usage());

            exit($e->getCode());
        }
    }

    /**
     * will setup some on exit function for this process
     *
     * @param AbstractWork $object
     * @return $this
     */
    protected function setupExit(AbstractWork $object)
    {
        $exitHandler = new ExitHandler();
        // Handling fatal errors and save object back to message queue
        $exitHandler->addCallback(function(AbstractWork $object, MessagesService $queue, $stats){

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

                $queue->send($object, Manager::QUEUE_STATE_FINISHED);

        }, array($object, $this->queue, &$this->stats))
        // Debug printing
        ->addCallback(function(OutputHandler $output){ $output->debug('Finished', posix_getpid(), OutputHandler::PROCESS_CHILD); }, array($this->output))
        // Release semaphore
        ->addCallback(function(SemaphoreService $sem){ $sem->release(); }, array($this->sem));

        return $this;
    }

    protected function setTimeOut(AbstractWork &$object)
    {
        if (null !== $timeout = $object->getTimeout()) {
                declare(ticks = 1);
                pcntl_alarm($timeout);
                pcntl_signal(SIGALRM, function() use ($timeout, &$object){
                    $object->setSuccess(false);
                    $object->setError(sprintf('timeout exceeded: %s', $timeout));
                    trigger_error(sprintf('timeout exceeded: %s', $timeout), E_USER_ERROR);
                });
        }

        return $this;
    }
}