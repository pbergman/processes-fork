<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Semaphore;

/**
 * Class SemaphoreException
 *
 * @package PBergman\Semaphore
 */
class SemaphoreException extends \Exception
{

    /**
     * @return SemaphoreException
     * @throws SemaphoreException
     */
    public static function couldNotCreateIdentifier()
    {
        throw new self("Could not create a positive semaphore identifier");
    }

    /**
     * @return SemaphoreException
     * @throws SemaphoreException
     */
    public static function couldNotAcquireSemaphore()
    {
        throw new self("Could not acquire semaphore");
    }

    /**
     * @return SemaphoreException
     * @throws SemaphoreException
     */
    public static function couldNotReleaseSemaphore()
    {
        throw new self("Could not release semaphore");
    }

    /**
     * @return SemaphoreException
     * @throws SemaphoreException
     */
    public static function couldNotRemoveSemaphore()
    {
        throw new self("Could not remove semaphore");
    }

    /**
     * @param   mixed   $key
     * @return  SemaphoreException
     * @throws  SemaphoreException
     */
    public static function invalidKeyGiven($key)
    {
        throw new self(sprintf("Invalid key given: %s, key should be a a numeric shared memory segment ID", $key));
    }
}