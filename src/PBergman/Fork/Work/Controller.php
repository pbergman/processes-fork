<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Work;

use PBergman\Fork\Container;
use PBergman\Fork\Output\LogFormatter;
use PBergman\Fork\ErrorHandler;
use PBergman\SystemV\IPC\Messages\Sender;

/**
 * Class Controller
 *
 * @package PBergman\Fork\Work
 */
class Controller
{
    /** @var Sender  */
    private $sender;
    /** @var int  */
    private $start;

    /**
     * @param Sender      $sender
     * @param Container   $container
     */
    public function __construct(Sender $sender, Container $container)
    {
        // For debugging set start time
        $this->start     = (int) microtime(true);
        $this->sender    = $sender;
        $this->container = $container;
    }

    /**
     * main controller for child
     *
     * @param AbstractWork $object
     */
    public function run(AbstractWork &$object)
    {
        /** @var \PBergman\Fork\Output\OutputInterface $output */
        $output = $this->container['output'];

        $output->write((new LogFormatter(LogFormatter::PROCESS_CHILD))->format(sprintf('Starting: %s', $object->getName())));

        // Set pids
        $object->setPid($this->container['helper.identifier']->getPid());

        // Setup exit function and check timeout
        $this->setupExit($object)
             ->checkTimeOut($object);

        // Try execute child process
        try {

            $object->execute($output);
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

        $sender     = $this->sender;
        $start      = $this->start;
        /** @var \PBergman\Fork\Output\Output $output */
        $output     = $this->container['output'];
        /** @var \PBergman\SystemV\IPC\Semaphore\Service $semaphore */
        $semaphore  = $this->container['semaphore'];
        /** @var \PBergman\Fork\Helper\ExitHelper $exitHelper */
        $exitHelper = $this->container['helper.exit'];

        $exitHelper->clear()->register(function()  use ($object, $sender, $start, $output, $semaphore) {

            // Handling fatal errors and save object back to message queue
            if (false !== $error = ErrorHandler::hasError(E_ERROR | E_USER_ERROR, true)) {
                $object->setSuccess(false)
                    ->setExitCode(255)
                    ->setDuration((microtime(true) - $start))
                    ->setPid(posix_getpid())
                    ->setUsage(memory_get_usage())
                    ->setError(sprintf("Fatal error: %s on line %s in file %s", $error['message'], $error['line'], $error['file']));
            }


            $send = $sender->setData($object)
                           ->setType(posix_getpid())
                           ->push();

            if (false === $send->isSuccess()) {
                trigger_error(sprintf('Failed to send message, %s(%s)', $sender->getError(), $sender->getErrorCode()), E_USER_ERROR);

            }

            // Print some debugging when finished
            $output->write((new LogFormatter(LogFormatter::PROCESS_CHILD))->format(
                sprintf('Finished: %s (%s MB/%s s)',
                    $object->getName(),
                    round($object->getUsage() /  1024 / 1024, 2),
                    round($object->getDuration(), 2)
                )
            ));

            // Release semaphore for queue
            $semaphore->release();

        });

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
            /* @var \PBergman\Fork\Helper\SignalHelper $signal */
            $signal = $this->container['helper.signal'];
            $signal->setAlarm($timeout, function() use ($timeout, $object) {
                $message = sprintf('timeout exceeded: %s second(s)', $timeout);
                $object->setSuccess(false)->setError($message);
                trigger_error($message, E_USER_ERROR);
            });

        }

        return $this;
    }
}