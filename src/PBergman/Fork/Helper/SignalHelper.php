<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Helper;

declare(ticks = 1);

/**
 * Class SignalHelper
 *
 * @package PBergman\Fork\Helper
 */
class SignalHelper
{
    protected $actions;
    protected $callbacks;

    /**
     * register action by signal
     *
     * @param int       $signal
     * @param callable  $callback
     */

    public function register($signal, callable $callback)
    {
        if (!is_array($signal)) {
            $signal = (array) $signal;
        }

        $callbackHash = spl_object_hash($callback);

        foreach($signal as $sig) {
            if (!isset($this->actions[$sig])) {

                pcntl_signal($sig, function($sig) {
                    if (isset($this->actions[$sig])) {
                        foreach($this->actions[$sig] as $callbackHash) {
                            if (isset($this->callbacks[$callbackHash])) {
                                call_user_func($this->callbacks[$callbackHash], $sig);
                            }
                        }
                    }
                });

                if (!isset($this->callbacks[$callbackHash])) {
                    $this->callbacks[$callbackHash] = $callback;
                }

                $this->actions[$sig][] = $callbackHash;

            } elseif (!in_array($this->actions[$sig], $callbackHash)) {
                $this->actions[$sig][] = $callbackHash;
            }
        }
    }

    protected function updateCallbacks(callable $callback)
    {
        $callbackHash = spl_object_hash($callback);

        if (!isset($this->callbacks[$callbackHash])) {
            $this->callbacks[$callbackHash] = $callback;
        }
    }

    /**
     * un register signal
     *
     * @param $signal
     */
    public function unRegisterSignal($signal)
    {
        if (isset($this->actions[$signal])) {
            unset($this->actions[$signal]);
        }
    }

    /**
     * will set alarm, for running (given) callback-
     *
     * @param int       $timeout
     * @param callable  $callback
     */
    public function setAlarm($timeout, callable $callback)
    {
        pcntl_alarm($timeout);

        $this->register(SIGALRM, $callback);

    }

}