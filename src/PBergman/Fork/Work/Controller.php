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

declare(ticks = 1);

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
     */
    public function __construct(OutputHandler $output, MessagesService $queue, SemaphoreService $sem)
    {
        // For debugging set start time
        $this->start  = microtime(true);
        $this->queue  = $queue;
        $this->output = $output;
        $this->sem    = $sem;
    }

    public function run($ppid)
    {
        // Enable custom error handler
        ErrorHandler::enable($this->output);
        /** @var AbstractWork $object */
        $this->queue->receive(Manager::QUEUE_STATE_TODO, $msgtype, 10000, $object);

        $this->output->debug(sprintf('Starting: %s', $object->getName()), posix_getpid(), OutputHandler::PROCESS_CHILD);

        // Set pids
        $object->setParentPid($ppid)->setPid(posix_getpid());

        // Setup exit function and check timeout
        $this->setupExit($object)->checkTimeOut($object);

        // Try execute child process
        try {

            $object->execute();
            $object->setDuration((microtime(true) - $this->start))
                   ->setUsage(memory_get_usage());

            exit(0);

        } catch(\Exception $e) {

            $object->setSuccess(false)
                ->setError($e->getMessage())
                ->setDuration((microtime(true) - $this->start))
                ->setUsage(memory_get_usage());

            exit($e->getCode());
        }
    }

    /**
     * will setup some on exit function for this process
     *
     * @param   AbstractWork  $object
     *
     * @return  $this
     */
    protected function setupExit(AbstractWork $object)
    {
        $exitHandler = new ExitHandler();
        // Handling fatal errors and save object back to message queue
        $exitHandler->addCallback(function(AbstractWork $object, MessagesService $queue, $startTime){

                if (null !== $error = error_get_last()) {
                    if ($error['type'] === E_ERROR) {
                        $object->setSuccess(false)
                            ->setExitCode(255)
                            ->setDuration((microtime(true) - $startTime))
                            ->setPid(posix_getpid())
                            ->setUsage(memory_get_usage())
                            ->setError(sprintf("Fatal error: %s on line %s in file %s", $error['message'], $error['line'], $error['file']));
                    }
                }

                $queue->send($object, Manager::QUEUE_STATE_FINISHED);

        }, array($object, $this->queue, $this->start))
        // Debug printing
        ->addCallback(function(OutputHandler $output){ $output->debug('Finished', posix_getpid(), OutputHandler::PROCESS_CHILD); }, array($this->output))
        // Release semaphore
        ->addCallback(function(SemaphoreService $sem){ $sem->release(); }, array($this->sem));

        return $this;
    }

    /**
     * will setup timeout
     *
     * @param AbstractWork $object
     * @return $this
     */
    protected function checkTimeOut(AbstractWork &$object)
    {
        if (null !== $timeout = $object->getTimeout()) {
                pcntl_alarm($timeout);
                pcntl_signal(SIGALRM, function() use ($timeout, &$object){
                    $message = sprintf('timeout exceeded: %s second(s)', $timeout);

                    $object->setSuccess(false)
                           ->setError($message);

                    trigger_error($message, E_USER_ERROR);
                });
        }

        return $this;
    }
}