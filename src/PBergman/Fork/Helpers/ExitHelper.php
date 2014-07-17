<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Helpers;

/**
 * Class ExitRegister
 *
 * little helper class for children that
 * can add callbacks to run on exit
 *
 * @package PBergman\Fork\Helpers
 */
class ExitHelper
{
    private $callback = array();

    public function __construct()
    {
        register_shutdown_function(array($this, 'run'));
    }

    /**
     * the exit runner
     */
    public function run()
    {
        foreach($this->callback as $callback) {
            call_user_func_array($callback[0], $callback[1]);
        }
    }

    /**
     * @return array
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * add exit callback
     *
     * @param   callable  $callback
     * @param   array     $arguments
     * @return  $this
     */
    public function addCallback(callable $callback, array $arguments = array())
    {
        $this->callback[] = array($callback, $arguments);
        return $this;
    }

    /**
     * set all exit callbacks
     *
     * @param array $callbacks
     */
    public function setCallback(array $callbacks)
    {
        $this->callback = array();

        foreach($callbacks as $callback) {

            if (is_array($callback)) {
                $this->addCallback($callback[0], $callback[1]);
            }

            if (is_callable($callback)) {
                $this->addCallback($callback);
            }
        }
    }


}