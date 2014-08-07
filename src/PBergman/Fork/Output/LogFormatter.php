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
    protected $label;

    protected $debug = array(
        self::PROCESS_PARENT    => 'PARENT',
        self::PROCESS_CHILD     => 'CHILD',
        self::PROCESS_ERROR     => 'ERROR',
        self::PROCESS_WARNING   => 'WARNING',
    );

    /**
     * @param int   $label          Label, can be one predefined constant or custom string
     * @param null  $pid            the pid of process, if left to null it will get pid from current process
     * @param bool  $shortLabel     with custom label and this true it will shorten the label to 5 characters and
     *                              append .. to end of string so for example SomeClassName becomes SomeC..
     */
    function __construct($label = self::PROCESS_PARENT, $pid = null, $shortLabel = true)
    {
        $this->pid   = (is_null($pid)) ? posix_getpid() : $pid;

        if (is_int($label) && array_key_exists($label, $this->debug)) {
            $this->label = $this->debug[$label];
        } else {
            $this->label = ($shortLabel) ? substr($label, 0, 5) . '..' : $label;
        }
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
        return sprintf("%s [%-7s] [%05d] %s",  date('Y-m-d H:i:s'), $this->label, $this->pid, $message);
    }
}