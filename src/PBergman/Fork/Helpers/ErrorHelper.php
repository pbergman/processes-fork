<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Helpers;

use PBergman\Fork\Output\FormatInterface;
use PBergman\Fork\Output\LogFormatter;
use PBergman\Fork\Output\OutputBuffer;
use PBergman\Fork\Output\OutputInterface;

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
     * @param OutputInterface   $output
     * @param null              $types
     */
    static function enable(OutputInterface $output, $types = null)
    {
        if (is_null($types)) {
            $types = E_ALL | E_STRICT;
        }

        error_reporting($types);

        set_error_handler(function($errno, $errstr, $errfile, $errline) use ($output) {

            $formatter = new LogFormatter(
                ($errno === E_USER_ERROR | E_ERROR) ? LogFormatter::PROCESS_ERROR : LogFormatter::PROCESS_WARNING,
                posix_getpid()
            );

            /** @var OutputBuffer $buffer */
            $buffer = $output
                        ->getBuffer()
                        ->setFormatter($formatter)
                        ->add(sprintf("%s: %s in file: %s(%s)", static::$errors[$errno], $errstr, $errfile, $errline));



            if ($output->isVerbose()) {
                static::printBackTrace($buffer);
            }

            $buffer->write();

            exit(1);

        }, $types);
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
        return restore_error_handler();
    }

}