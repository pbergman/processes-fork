<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Output;

/**
 * Class OutputBuffer
 *
 * @package PBergman\Fork\Output
 */
class OutputBuffer
{
    /** @var OutputInterface */
    protected $output;
    /** @var array */
    protected $buffer;
    /** @var FormatInterface */
    protected $formatter;
    /** @var string  */
    protected $bufferDelimiter = PHP_EOL;

    /**
     * @param OutputInterface $output
     */
    function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * add line to buffer
     *
     * @param   string $message
     * @return \PBergman\Fork\Output\OutputBuffer;
     */
    public function add($message)
    {
        $this->buffer[] = $message;
        return $this;
    }

    /**
     * writes buffered messages
     */
    public function write()
    {

        if (!is_null($this->formatter)) {
            array_walk($this->buffer, function(&$value){
                $value = $this->formatter->format($value);
            });
        }

        $this->output->write(implode($this->bufferDelimiter, $this->buffer));
        $this->buffer = array();
    }

    /**
     * @return string
     */
    public function getBufferDelimiter()
    {
        return $this->bufferDelimiter;
    }

    /**
     * @param   string $bufferDelimiter
     * @return  \PBergman\Fork\Output\OutputBuffer;
     */
    public function setBufferDelimiter($bufferDelimiter)
    {
        $this->bufferDelimiter = $bufferDelimiter;
        return $this;
    }

    /**
     * @return \PBergman\Fork\Output\FormatInterface
     */
    public function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * @param   \PBergman\Fork\Output\FormatInterface $formatter
     * @return  \PBergman\Fork\Output\OutputBuffer;
     */
    public function setFormatter(FormatInterface $formatter)
    {
        $this->formatter = $formatter;
        return $this;
    }


}