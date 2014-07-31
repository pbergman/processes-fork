<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Helper;

/**
 * Class ExitHelper
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
class ExitHelper
{
    /** @var \SplObjectStorage  */
    private $callback;
    /** @var IdentifierHelper  */
    private $identifier;

    /**
     * constructor setup SplObjectStorage
     * and registered shutdown function
     *
     * @param IdentifierHelper $identifier
     */
    public function __construct(IdentifierHelper $identifier)
    {
        $this->identifier = $identifier;
        $this->callback   = new \SplObjectStorage();

        register_shutdown_function(function(){
            $this->callback->rewind();
            while($this->callback->valid()) {
                if (is_null($this->callback->getInfo()) || $this->callback->getInfo() === $this->identifier->getPid()) {
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
        $this->callback->attach($callback, ($global ? null : $this->identifier->getPid()));

        return $this;
    }

    /**
     * Remove all registered exit callbacks
     * that are not bound to this process
     *
     * @return $this;
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

        return $this;

    }

    /**
     * clear all callback from storage
     *
     * @return $this;
     */
    public function clearAll()
    {
        $this->callback->removeAll($this->callback);
        return $this;
    }
}