<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Helpers;

/**
 * Class ErrorHelper
 *
 * @package PBergman\Fork\Helpers
 */
class ErrorHelper
{
    /**
     * error mapping
     *
     * @var array
     */
    private static $errors = array(
        1     => 'E_ERROR',
        2     => 'E_WARNING',
        4     => 'E_PARSE',
        8     => 'E_NOTICE',
        16    => 'E_CORE_ERROR',
        32    => 'E_CORE_WARNING',
        64    => 'E_COMPILE_ERROR',
        128   => 'E_COMPILE_WARNING',
        256   => 'E_USER_ERROR',
        512   => 'E_USER_WARNING',
        1024  => 'E_USER_NOTICE',
        2048  => 'E_STRICT',
        4096  => 'E_RECOVERABLE_ERROR',
        8192  => 'E_DEPRECATED',
        16384 => 'E_USER_DEPRECATED',
        32767 => 'E_ALL',
    );

    /**
     * set custom error handler
     *
     * @param OutputHelper      $output
     * @param null              $types
     * @param bool              $backtrace
     */
    static function enable(OutputHelper $output, $types = null, $backtrace = true)
    {
        if (is_null($types)) {
            $types = E_ALL | E_STRICT;
        }

        error_reporting(0);

        set_error_handler(function($errno, $errstr, $errfile, $errline) use ($output, $backtrace) {

            $caller = ($errno === E_USER_ERROR) ? OutputHelper::PROCESS_ERROR : OutputHelper::PROCESS_WARNING;

            $output->addToBuffer(sprintf("%s: %s in file: %s(%s)", static::$errors[$errno], $errstr, $errfile, $errline));

            if ($backtrace) {
                static::printBackTrace($output, $caller);
            }

            $output->printfBuffer(posix_getpid(), $caller);
            $output->resetBuffer();

        }, $types);
    }

    /**
     * print backtrace
     *
     * @param OutputHelper $output
     * @param string       $caller
     */
    static function printBackTrace(OutputHelper $output, $caller)
    {
        foreach (debug_backtrace() as $k => $v) {

            array_walk($v['args'], function (&$item, $key) {
                $item = var_export($item, true);
            });

            $output->addToBuffer(sprintf("#%d %s(%s): %s(%s)", $k,  $v['file'], $v['line'], (isset($v['class']) ? $v['class'] . '->' : null), $v['function'], implode(', ', $v['args'])));
        }
    }

    /**
     * restore error handler
     *
     * @return bool
     */
    static function restore()
    {
        return restore_error_handler();
    }

}