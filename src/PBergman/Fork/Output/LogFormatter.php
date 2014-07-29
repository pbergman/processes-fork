<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\Fork\Output;

/**
 * Class LogFormatter
 *
 * @package PBergman\Fork\Output
 */
class LogFormatter implements FormatInterface
{
    const PROCESS_PARENT   = 1;
    const PROCESS_CHILD    = 2;
    const PROCESS_ERROR    = 4;
    const PROCESS_WARNING  = 8;

    protected $pid;
    protected $id;

    protected $debug = array(
        self::PROCESS_PARENT    => 'PARENT',
        self::PROCESS_CHILD     => 'CHILD',
        self::PROCESS_ERROR     => 'ERROR',
        self::PROCESS_WARNING   => 'WARNING',
    );

    function __construct($id = self::PROCESS_PARENT, $pid = null)
    {
        $this->pid = (is_null($pid)) ? posix_getpid() : $pid;
        $this->id  = $id;
    }


    /**
     * will format string and return
     * formatted string back for printing
     *
     * @param  string $message
     * @return string
     */
    public function format($message)
    {
        return sprintf("%s [%-7s] [%-6d] %s",  date('Y-m-d H:i:s'), $this->debug[$this->id], $this->pid, $message);
    }
}