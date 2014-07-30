<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork;

use PBergman\Fork\Output\LogFormatter;
use PBergman\Fork\Output\OutputBuffer;
use PBergman\Fork\Output\OutputInterface;

/**
 * Class ErrorHelper
 *
 * @package PBergman\Fork\Helpers
 */
class ErrorHandler
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

    private static $oldErrorReporting;
    /** @var OutputInterface  */
    private static $output;
    /** @var array */
    private static $log;

    /**
     * set custom error handler
     *
     * @param OutputInterface   $output
     * @param null              $types
     */
    static function enable(OutputInterface $output, $types = null)
    {
        if (is_null($types)) {
            $types = E_ALL | E_STRICT;
        }

        self::$output = $output;

        self::$oldErrorReporting = error_reporting(0);

        set_error_handler(array('self', 'printErrors'), $types);

        register_shutdown_function(function(){

            if (false !== $error = self::hasError(E_ERROR|E_USER_ERROR, true)) {
                // No duplicated error messaging from same process
                if (!isset(self::$log[posix_getpid()][md5(serialize($error))])) {
                    self::$log[posix_getpid()][md5(serialize($error))] = 1;
                    self::printErrors($error['type'], $error['message'], $error['file'], $error['line']);
                }
            }

        });
    }

    /**
     * check if we got a error
     *
     * @param   int         $type           which type to check for can also give E_ERROR | E_USER_ERROR as argument
     * @param   bool        $returnErrors   set true to get errors returned, if false will return true on erros
     * @return  bool|array
     */
    public static function hasError($type = E_ERROR, $returnErrors = false)
    {
        $return = false;

        if (null !== $error = error_get_last()) {
            if ($error['type'] & ($type)) {
                $return = ($returnErrors) ? $error : true;
            }
        }

        return $return;
    }

    /**
     * prints error message
     *
     * @param int       $code
     * @param string    $message
     * @param string    $file
     * @param int       $line
     */
    private static function printErrors($code, $message, $file, $line)
    {

        $formatter = new LogFormatter(
            ($code === E_USER_ERROR | E_ERROR) ? LogFormatter::PROCESS_ERROR : LogFormatter::PROCESS_WARNING,
            posix_getpid()
        );

        /** @var OutputBuffer $buffer */
        $buffer = self::$output
            ->getBuffer()
            ->setFormatter($formatter)
            ->add(sprintf("%s: %s in file: %s(%s)", static::$errors[$code], $message, $file, $line));

        if (self::$output ->isVerbose()) {
            static::printBackTrace($buffer);
        }

        $buffer->write();
    }

    /**
     * print backtrace
     *
     * @param OutputBuffer $buffer
     */
    private static function printBackTrace(OutputBuffer $buffer)
    {
        foreach (debug_backtrace() as $k => $v) {

            array_walk($v['args'], function (&$item) {
                $item = var_export($item, true);
            });

            $buffer->add(sprintf("#%d %s(%s): %s(%s)", $k,  $v['file'], $v['line'], (isset($v['class']) ? $v['class'] . '->' : null), $v['function'], implode(', ', $v['args'])));
        }
    }

    /**
     * restore error handler
     *
     * @return bool
     */
    static function restore()
    {
        error_reporting(self::$oldErrorReporting);
        return restore_error_handler();
    }

}