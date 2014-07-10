<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\ProcessesFork;

/**
 * Class ErrorHandler
 *
 * @package PBergman\ProcessesFork
 */
class ErrorHandler
{
    /** @var OutputHandler */
    protected $output;
    protected $errors = array(
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
     * the error handler
     *
     * @param int       $errno
     * @param string    $errstr
     * @param string    $errfile
     * @param int       $errline
     */
    public function handler($errno, $errstr, $errfile, $errline)
    {
        $this->output->debug(sprintf("%s: %s on line %s in file %s", $this->errors[$errno], $errstr, $errline, $errfile), null, OutputHandler::PROCESS_CHILD);
    }


    public function __construct(OutputHandler $output, $types = null)
    {
        if (is_null($types)) {
            $types = E_ALL | E_STRICT;
        }

        $this->output = $output;
        set_error_handler(array($this, 'handler'), $types);
        error_reporting(0);
    }


}