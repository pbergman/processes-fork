<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Output;

/**
 * Interface OutputInterface
 *
 * @package PBergman\Fork\Output
 */
interface OutputInterface
{
    /**
     * returns the current stream used
     *
     * @return resource
     */
    public function getStream();

    /**
     * will sets stream, for example fopen('php://stdout', 'w')
     * @param   resource $stream
     * @return  \PBergman\Fork\Output\OutputInterface;
     */
    public function setStream($stream);

    /**
     * write string to the set resource
     *
     * @param string    $message
     * @param bool      $newline
     *
     * @return mixed
     */
    public function write($message, $newline = true);

    /**
     * will return buffer helper or null if none defined
     *
     * @return null|\PBergman\Fork\Output\OutputInterface;
     */
    public function getBuffer();

    /**
     * @param   bool    $verbose
     * @return  \PBergman\Fork\Output\OutputInterface;
     */
    public function setVerbose($verbose);

    /**
     * @return bool
     */
    public function isVerbose();

}