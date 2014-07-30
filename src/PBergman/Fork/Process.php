<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork;

/**
 * Class Process
 *
 * @package PBergman\Fork
 */
class Process
{
    /** @var int  */
    private static $parent;

    /**
     * will set parent pid so we can identify
     * if process is child or parent process
     *
     * @throws \RuntimeException
     */
    public static function initialize()
    {
        if (is_null(self::$parent)) {
            self::$parent = posix_getpid();
        } else {
            throw new \RuntimeException('Parent is already set');
        }

    }

    /**
     * check if we are the parent process
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public static function isParent()
    {
        if (is_null(self::$parent)) {
            throw new \RuntimeException('Parent is not set, call initialize first');
        }

        return self::$parent === self::getPid();
    }

    /**
     * check if we are the child process
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public static function isChild()
    {
        if (is_null(self::$parent)) {
            throw new \RuntimeException('Parent is not set, call initialize first');
        }

        return self::$parent !== self::getPid();
    }

    /**
     * @return int
     */
    public static function getParentPid()
    {
        return self::$parent;
    }

    /**
     * @return int
     */
    public static function getPid()
    {
        return posix_getpid();
    }


}