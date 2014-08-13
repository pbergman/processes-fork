<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Output;

/**
 * Class Output
 *
 * @package PBergman\Fork\Output
 */
class Output implements OutputInterface
{
    /**  @var resource */
    protected $stream;
    /** @var bool  */
    protected $verbose = false;

    public function __construct()
    {
        $this->stream = fopen('php://stdout', 'w');
    }

    /**
     * returns the current stream used
     *
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * write string to the set resource
     *
     * @param   string  $message
     * @param   bool    $newline
     *
     * @throws  \RuntimeException
     *
     * @return mixed
     */
    public function write($message, $newline = true)
    {

        if (!$this->haveValidStream()) {
            throw new \RuntimeException('No stream set, declare a stream first with method setStream');
        }

        fwrite($this->stream, $message . ($newline ? "\x0a" : ''));
        fflush($this->stream);
    }

    /**
     * will sets stream, for example fopen('php://stdout', 'w')
     *
     * @param   resource $stream
     * @throws  \InvalidArgumentException
     * @return  \PBergman\Fork\Output\Output;
     */
    public function setStream($stream)
    {
        if (!$this->haveValidStream()) {
            throw new \InvalidArgumentException('No valid stream given!');
        }
    }

    /**
     * check if got a valid stream defined
     *
     * @param  $stream
     * @return bool
     */
    protected function haveValidStream($stream = null)
    {
        $stream = (is_null($stream)) ? $this->stream : $stream;

        return !is_null($stream) && is_resource($stream) && 'stream' === get_resource_type($stream);
    }

    /**
     * @return OutputBuffer
     */
    public function getBuffer()
    {
        return new OutputBuffer($this);
    }

    /**
     * @param   bool    $verbose
     * @return  \PBergman\Fork\Output\Output
     */
    public function setVerbose($verbose)
    {
        $this->verbose = $verbose;
        return $this;
    }

    /**
     * @return bool
     */
    public function isVerbose()
    {
        return $this->verbose;
    }
}