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
     * @param OutputHelper $output
     * @param null $types
     */
    static function enable(OutputHelper $output, $types = null)
    {
        if (is_null($types)) {
            $types = E_ALL | E_STRICT;
        }

        set_error_handler(function($errno, $errstr, $errfile, $errline) use ($output) {
            $output->debug(sprintf("%s: %s on line %s in file %s", static::$errors[$errno], $errstr, $errline, $errfile), posix_getpid(), OutputHelper::PROCESS_WARNING);
        }, $types);

        error_reporting(0);
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