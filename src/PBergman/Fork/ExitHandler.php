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
    private static $callback;

    public static function initialize()
    {
        self::$callback = new \SplObjectStorage();

        register_shutdown_function(function(){

            self::$callback->rewind();

            while(self::$callback->valid()) {

                if (is_null(self::$callback->getInfo()) || self::$callback->getInfo() === Process::getPid()) {
                    $callback = self::$callback->current();
                    call_user_func($callback);
                    if (!is_null(self::$callback->getInfo())) {
                        self::$callback->detach($callback);
                    }
                }

                self::$callback->next();
            }
        });
    }

    /**
     * @return \SplObjectStorage
     */
    public static function getCallbacks()
    {
        return self::$callback;
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
    public static function register(callable $callback, $global = false)
    {
        self::$callback->attach($callback, ($global ? null : Process::getPid()));
    }

    /**
     * Remove all registered exit callbacks
     */
    public static function clear()
    {
        self::$callback->removeAll(self::$callback);
    }
}