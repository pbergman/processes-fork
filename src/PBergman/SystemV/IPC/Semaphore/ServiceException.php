<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace PBergman\SystemV\IPC\Semaphore;

/**
 * Class ServiceException
 *
 * @package PBergman\SystemV\IPC\Semaphore
 */
class ServiceException extends \Exception
{
    /**
     * @return ServiceException
     * @throws ServiceException
     */
    public static function couldNotCreateIdentifier()
    {
        throw new self("Could not create a positive semaphore identifier");
    }

    /**
     * @return ServiceException
     * @throws ServiceException
     */
    public static function couldNotAcquireSemaphore()
    {
        throw new self("Could not acquire semaphore");
    }

    /**
     * @return ServiceException
     * @throws ServiceException
     */
    public static function couldNotReleaseSemaphore()
    {
        throw new self("Could not release semaphore");
    }

    /**
     * @return ServiceException
     * @throws ServiceException
     */
    public static function couldNotRemoveSemaphore()
    {
        throw new self("Could not remove semaphore");
    }

    /**
     * @param   mixed   $key
     * @return  ServiceException
     * @throws  ServiceException
     */
    public static function invalidKeyGiven($key)
    {
        throw new self(sprintf("Invalid key given: %s, key should be a a numeric shared memory segment ID", $key));
    }
}