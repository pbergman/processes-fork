<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Helper;

/**
 * Class Identifier
 *
 * @package PBergman\Fork
 */
class IdentifierHelper
{
    /** @var int  */
    private $parent;

    /**
     * will set parent pid so we can identify
     * if process is child or parent process
     */
    public function __construct()
    {
        $this->parent = $this->getPid();
    }

    /**
     * check if we are the parent process
     *
     * @return bool
     */
    public function isParent()
    {
        return $this->parent === $this->getPid();
    }

    /**
     * check if we are the child process
     *
     * @return bool
     */
    public function isChild()
    {
        return $this->parent !== $this->getPid();
    }

    /**
     * @return int
     */
    public function getParentPid()
    {
        return $this->parent;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return posix_getpid();
    }


}