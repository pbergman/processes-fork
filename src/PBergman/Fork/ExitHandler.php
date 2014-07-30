<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork;

/**
 * Class ExitRegister
 *
 * a static class where exit call back can be defined,
 * function that needed to be called just before exit
 *
 * every callback is only executed for child/parent
 * that registered it, this is done by setting the
 * pid from process to the info of the SplObjectStorage
 *
 * @package PBergman\Fork
 */
class ExitHandler
{
    /** @var \SplObjectStorage  */
    private $callback;
    /** @var Identifier  */
    private $identifier;

    /**
     * constructor setup SplObjectStorage
     * and registered shutdown function
     *
     * @param Identifier $identifier
     */
    public function __construct(Identifier $identifier)
    {
        $this->identifier = $identifier;
        $this->callback   = new \SplObjectStorage();

        register_shutdown_function(function(){
            $this->callback->rewind();
            while($this->callback->valid()) {
                if (is_null($this->callback->getInfo()) || $this->callback->getInfo() === Process::getPid()) {
                    $callback = $this->callback->current();
                    call_user_func($callback);
                    // Do not attach a global callback
                    if (!is_null($this->callback->getInfo())) {
                        $this->callback->detach($callback);
                    }
                }
                $this->callback->next();
            }
        });
    }


    /**
     * @return \SplObjectStorage
     */
    public function getCallbacks()
    {
        return $this->callback;
    }

    /**
     * add exit callback
     *
     * @param   callable  $callback
     * @param   bool      $global       if set to true will execute callback to all child/parent
     *                                  process and wont de detached after it was called
     *
     * @return  $this
     */
    public function register(callable $callback, $global = false)
    {
        $this->$callback->attach($callback, ($global ? null : $this->identifier->getPid()));
    }

    /**
     * Remove all registered exit callbacks
     * that are not bound to this process
     */
    public function clear()
    {
        $this->callback->rewind();

        while($this->callback->valid()) {

            if (!is_null($this->callback->getInfo()) && $this->callback->getInfo() !== $this->identifier->getPid()) {
                $this->callback->detach($this->callback->current());
            }

            $this->callback->next();
        }

    }

    /**
     * clear all callback from storage
     */
    public function clearAll()
    {
        $this->callback->removeAll($this->callback);
    }
}