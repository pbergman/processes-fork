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
use PBergman\SystemV\IPC\Semaphore\Service as Semaphore;

/**
 * Class Controller
 *
 * @package PBergman\Fork\Work
 */
class Controller
{
    /** @var int  */
    private $start;
    /** @var Container|\PBergman\Fork\Output\Output[]|\PBergman\Fork\Helper\IdentifierHelper[]|\PBergman\Fork\Helper\ExitHelper[]|\PBergman\SystemV\IPC\Semaphore\Service[] */
    private $container;

    /**
     * @param Container   $container
     */
    public function __construct(Container $container)
    {
        // For debugging set start time
        $this->start     = (int) microtime(true);
        $this->container = $container;
    }

    /**
     * main controller for child
     *
     * @param AbstractWork $object
     */
    public function run(AbstractWork &$object)
    {
        $this->write('Starting: %s', array($object->getName()), !$object->isQuiet());

        $object->setPid($this->container['helper.identifier']->getPid());

        // Some pre-run setups
        $this->checkTimeOut($object)->setExit($object);

        // Try execute child process
        try {

            $object->execute($this->container);
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
     * will check if timeout is set, if
     * so will set alarm for timeout
     *
     * @param AbstractWork $object
     * @return \PBergman\Fork\Work\Controller
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

    /**
     * set some exit functions that are going to be
     * called when work is finished just before exit
     *
     * @param AbstractWork $object
     */
    protected function setExit(AbstractWork &$object)
    {
        $this->container['helper.exit']->clear()->register(function()  use ($object) {

            // Handling fatal errors and save object back to message queue
            if (false !== $error = ErrorHandler::hasError(E_ERROR | E_USER_ERROR, true)) {
                $object->setSuccess(false)
                    ->setExitCode(255)
                    ->setDuration((microtime(true) - $this->start))
                    ->setPid(posix_getpid())
                    ->setUsage(memory_get_usage())
                    ->setError(sprintf("Fatal error: %s on line %s in file %s", $error['message'], $error['line'], $error['file']));
            }

            /** @var \PBergman\SystemV\IPC\Messages\Sender $sender */
            $sender = $this->container['queue.sender'];
            $sender->setData($object)
                ->setType(posix_getpid())
                ->push();

            if (false === $sender->isSuccess()) {
                trigger_error(sprintf('Failed to send message, %s(%s)', $sender->getError(), $sender->getErrorCode()), E_USER_ERROR);

            }

            // Print some debugging when finished
            $this->write('Finished: %s (%s MB/%s s)', array($object->getName(), round($object->getUsage() /  1024 / 1024, 2), round($object->getDuration(), 2)), !$object->isQuiet());

            // Release semaphore for queue
            $this->container['instance.semaphore']->release();
        });
    }

    /**
     * helper to print some debugging
     *
     * @param   string  $string
     * @param   array   $args
     * @param   bool    $print
     *
     * @return  \PBergman\Fork\Work\Controller
     */
    protected function write($string, array $args = array(), $print = true)
    {
        if ($print) {
            $this->container['output']->write((new LogFormatter(LogFormatter::PROCESS_CHILD))->format(vsprintf($string, $args)));
        }
        return $this;
    }
}