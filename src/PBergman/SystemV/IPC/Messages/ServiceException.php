<?php
/**
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\SystemV\IPC\Messages;


/**
 * Class SystemVMessageException
 *
 * @package PBergman\SystemV\IPC\Messages
 */
class ServiceException extends \Exception
{

    /**
     * @param   mixed   $error
     * @return  ServiceException
     * @throws  ServiceException
     */
    public static function failedToReceive($error)
    {
        throw new self(sprintf("[%d] Could not receive message", (int) $error));
    }

    /**
     * @throws  ServiceException
     * @return  ServiceException
     */
    public static function failedToSetQueueInformation()
    {
        throw new self('Failed to set queue information');
    }

    /**
     * @param   string   $field
     * @param   array    $all
     * @return  ServiceException
     * @throws  ServiceException
     */
    public static function invalidSetField($field, array $all)
    {
        throw new self(sprintf("Invalid set field: %s, allowed field names: ", $field, implode(', ', $all)));
    }


    /**
     * @param   mixed   $type
     * @return  ServiceException
     * @throws  ServiceException
     */
    public static function invalidMessageType($type)
    {
        throw new self(sprintf("Message type: %s for type: %s is invalid this should be a numeric value", $type, gettype($type)));
    }


    /**
     * @param   mixed   $error
     * @return  ServiceException
     * @throws  ServiceException
     */
    public static function failedToSend($error)
    {
        throw new self(sprintf("[%d] Could not send message", (int) $error));
    }

    /**
     * @param   mixed   $id
     * @return  ServiceException
     * @throws  ServiceException
     */
    public static function failedToRemove($id)
    {
        throw new self(sprintf("Could not remove message queue: %d", (int) $id));
    }

    /**
     * @param   mixed   $key
     * @return  ServiceException
     * @throws  ServiceException
     */
    public static function invalidKeyGiven($key)
    {
        throw new self(sprintf("Invalid key given: %s, key should be a a numeric message queue id", $key));
    }
}