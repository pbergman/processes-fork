<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Semaphore;

/**
 * Class SharedMemoryException
 *
 * @package PBergman\Semaphore
 */
class SharedMemoryException extends \Exception
{
    /**
     * @param   mixed   $key
     * @return  SharedMemoryException
     * @throws  SharedMemoryException
     */
    public static function invalidKeyGiven($key)
    {
        throw new self(sprintf("Invalid key given: %s, key should be a a numeric shared memory segment ID", $key));
    }

    /**
     * @return  SharedMemoryException
     * @throws  SharedMemoryException
     */
    public static function couldNotSaveVar()
    {
        throw new self('Could not save variable in shared memory');
    }

    /**
     * @param   mixed   $id
     * @return  SharedMemoryException
     * @throws  SharedMemoryException
     */
    public static function couldNotOpenSegment($id)
    {
        throw new self(sprintf('Could not open shared memory segment with id: ', $id));
    }
}
