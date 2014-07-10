<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Semaphore;

/**
 * Class MessageQueueException
 *
 * @package PBergman\Semaphore
 */
class MessageQueueException extends \Exception
{

    /**
     * @param   mixed   $error
     * @return  MessageQueueException
     * @throws  MessageQueueException
     */
    public static function failedToReceive($error)
    {
        throw new self(sprintf("[%d] Could not receive message", (int) $error));
    }

    /**
     * @throws MessageQueueException
     * @return  MessageQueueException
     */
    public static function failedToSetQueueInformation()
    {
        throw new self('Failed to set queue information');
    }

    /**
     * @param   string   $field
     * @param   array    $all
     * @return  MessageQueueException
     * @throws  MessageQueueException
     */
    public static function invalidSetField($field, array $all)
    {
        throw new self(sprintf("Invalid set field: %s, allowed field names: ", $field, implode(', ', $all)));
    }


    /**
     * @param   mixed   $type
     * @return  MessageQueueException
     * @throws  MessageQueueException
     */
    public static function invalidMessageType($type)
    {
        throw new self(sprintf("Message type: %s for type: %s is invalid this should be a int larger than 0", $type, gettype($type)));
    }


    /**
     * @param   mixed   $error
     * @return  MessageQueueException
     * @throws  MessageQueueException
     */
    public static function failedToSend($error)
    {
        throw new self(sprintf("[%d] Could not send message", (int) $error));
    }

    /**
     * @param   mixed   $id
     * @return  MessageQueueException
     * @throws  MessageQueueException
     */
    public static function failedToRemove($id)
    {
        throw new self(sprintf("Could not remove message queue: %d", (int) $id));
    }

    /**
     * @param   mixed   $key
     * @return  MessageQueueException
     * @throws  MessageQueueException
     */
    public static function invalidKeyGiven($key)
    {
        throw new self(sprintf("Invalid key given: %s, key should be a a numeric message queue id", $key));
    }
}