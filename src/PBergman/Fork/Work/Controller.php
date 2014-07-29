<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Work;

use PBergman\Fork\Output\LogFormatter;
use PBergman\SystemV\IPC\Semaphore\Service as SemaphoreService;
use PBergman\SystemV\IPC\Messages\Sender;
use PBergman\Fork\Output\OutputInterface;
use PBergman\Fork\Helpers\ExitHelper   as ExitHandler;

declare(ticks = 1);
/**
 * Class Controller
 *
 * @package PBergman\Fork\Work
 */
class Controller
{
    /** @var OutputInterface  */
    private $output;
    /** @var Sender  */
    private $sender;
    /** @var SemaphoreService  */
    private $sem;
    /** @var int  */
    private $start;

    /**
     * @param OutputInterface   $output
     * @param Sender            $sender
     * @param SemaphoreService  $sem
     */
    public function __construct(OutputInterface $output, Sender $sender, SemaphoreService $sem)
    {
        // For debugging set start time
        $this->start  = (int) microtime(true);
        $this->sender = $sender;
        $this->output = $output;
        $this->sem    = $sem;
    }

    /**
     * main controller for child
     *
     * @param AbstractWork $object
     */
    public function run(AbstractWork &$object)
    {

        $this->output->write((new LogFormatter(LogFormatter::PROCESS_CHILD))->format(sprintf('Starting: %s', $object->getName())));

        // Set pids
        $object->setPid(posix_getpid());

        // Setup exit function and check timeout
        $this->setupExit($object)
             ->checkTimeOut($object);

        // Try execute child process
        try {

            $object->execute($this->output);
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
        $onExit = new ExitHandler();
        $onExit
            /**
             * Handling fatal errors and save object back to message queue
             */
            ->addCallback(function(AbstractWork $object, Sender $sender, $startTime){

                if (null !== $error = error_get_last()) {
                    if ($error['type'] & (E_ERROR | E_USER_ERROR)) {
                        $object->setSuccess(false)
                            ->setExitCode(255)
                            ->setDuration((microtime(true) - $startTime))
                            ->setPid(posix_getpid())
                            ->setUsage(memory_get_usage())
                            ->setError(sprintf("Fatal error: %s on line %s in file %s", $error['message'], $error['line'], $error['file']));
                    }
                }

                $send = $sender->setData($object)
                               ->setType(posix_getpid())
                               ->push();

                if (false === $send->isSuccess()) {
                    trigger_error(sprintf('Failed to send message, %s(%s)', $sender->getError(), $sender->getErrorCode()), E_USER_ERROR);
                }

        }, array($object, $this->sender, $this->start))
            /**
             * Print some debugging when finished
             */
            ->addCallback(function(OutputInterface $output, AbstractWork $object){

                $output->write((new LogFormatter(LogFormatter::PROCESS_CHILD))->format(
                    sprintf('Finished: %s (%s MB/%s s)',
                        $object->getName(),
                        round($object->getUsage() /  1024 / 1024, 2),
                        round($object->getDuration(), 2)
                    )
                ));

        }, array($this->output, $object))
            /**
             * Release semaphore for queue when finished
             */
            ->addCallback(function(SemaphoreService $sem){
                $sem->release();
        }, array($this->sem));

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