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
class OutputHandler
{
    const PROCESS_PARENT = 1;
    const PROCESS_CHILD  = 2;

    /**  @var resource */
    protected $stream;

    protected $debug = array(
        self::PROCESS_PARENT => 'PARENT',
        self::PROCESS_CHILD  => 'CHILD',
    );

    public function __construct()
    {
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

    /**
     * write string to the  set resource
     *
     * @param string    $message
     * @param bool      $newline
     * @throws \RuntimeException
     */
    public function write($message, $newline = true)
    {
        if (false === @fwrite($this->stream, $message.($newline ? PHP_EOL : ''))) {
            // should never happen
            throw new \RuntimeException('Unable to write output.');
        }

        fflush($this->stream);
    }

    /**
     * will do a formatted print
     *
     * @param $message
     * @param $pid
     * @param int $calling
     */
    public function debug($message, $pid, $calling = self::PROCESS_PARENT)
    {
        $this->write(sprintf("%s [%-6s] [%-6d] %s",  date('"Y-m-d H:i:s"'), $this->debug[$calling], $pid, $message));
    }


}