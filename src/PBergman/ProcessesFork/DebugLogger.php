<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\ProcessesFork;

class DebugLogger
{
    protected $stream;
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

    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        echo sprintf("[%s] %s on line %s in file %s", $this->errors[$errno], $errstr, $errline, $errfile);
    }


    public function __construct()
    {

        set_error_handler(array($this, 'errorHandler'));

        $this->stream = fopen('php://stdout', 'w');
    }

    /**
     * @return mixed
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @param   mixed $stream
     * @return  $this
     */
    public function setStream($stream)
    {
        $this->stream = $stream;
        return $this;
    }

    protected function write($message, $newline = true)
    {

        $message = date('c') . ' ' .$message;

        if (false === @fwrite($this->stream, $message.($newline ? PHP_EOL : ''))) {
            // should never happen
            throw new \RuntimeException('Unable to write output.');
        }

        fflush($this->stream);
    }
}