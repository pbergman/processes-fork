<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace PBergman\SystemV\IPC\SharedMemory;

/**
 * Class ServiceException
 *
 * @package PBergman\SystemV\IPC\SharedMemory
 */
class ServiceException extends \Exception
{
    /**
     * @param   mixed   $key
     * @return  ServiceException
     * @throws  ServiceException
     */
    public static function invalidKeyGiven($key)
    {
        throw new self(sprintf("Invalid key given: %s, key should be a a numeric shared memory segment ID", $key));
    }

    /**
     * @return  ServiceException
     * @throws  ServiceException
     */
    public static function couldNotSaveVar()
    {
        throw new self('Could not save variable in shared memory');
    }

    /**
     * @param   mixed   $id
     * @return  ServiceException
     * @throws  ServiceException
     */
    public static function couldNotOpenSegment($id)
    {
        throw new self(sprintf('Could not open shared memory segment with id: ', $id));
    }
}