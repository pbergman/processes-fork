<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\SystemV\IPC\Messages;

/**
 * Class Service
 *
 * @package PBergman\SystemV\IPC\Messages
 */
class Service
{
    private $connection;
    private $key;

    const RESOURCE_TYPE = 'sysvmsg queue';

    /**
     * will initialize message queue
     *
     * @param   int   $key
     * @param   int   $perms
     * @throws  ServiceException
     */
    public function __construct($key, $perms = 0666)
    {
        if (!is_numeric($key)) {
            throw ServiceException::invalidKeyGiven($key);
        }

        $this->key   = $key;
        $this->connection = msg_get_queue($key, $perms);
    }

    /**
     * check if message queue exists
     *
     * @param   int $key
     * @return  bool
     * @throws  ServiceException
     */
    public static function exists($key)
    {
        if (!is_numeric($key)) {
            throw ServiceException::invalidKeyGiven($key);
        }

        return msg_queue_exists($key);
    }

    /**
     * destroys the message queue specified by the queue. Only use this function
     * when all processes have finished working with the message queue and you
     * need to release the system resources held by it.
     *
     * @return bool
     * @throws ServiceException
     */
    public function remove()
    {
        if (false === $return = msg_remove_queue($this->connection)) {
            throw ServiceException::failedToRemove($this->connection);
        } else {
            return $return;
        }
    }

    /**
     * @return Sender
     */
    public function getSender()
    {
        return new Sender($this->connection);
    }

    /**
     * @return Receiver
     */
    public function getReceiver()
    {
        return new Receiver($this->connection);
    }


    /**
     * returns the message queue meta data for the message queue specified by the queue.
     * This is useful, for example, to determine which process sent the message that was just receive
     *
     * @return array
     */
    public function status()
    {
        return msg_stat_queue($this->connection);
    }

    /**
     *  Set information in the message queue data structure
     *
     * @param   array $data
     * @return  bool
     */
    public function set(array $data)
    {

        $validKeys = array(
            'msg_perm.uid',
            'msg_perm.gid',
            'msg_perm.mode',
            'msg_qbytes'
        );

        foreach($data as $key => $value) {
            if(!in_array($key, $validKeys)) {
                ServiceException::invalidSetField($key, $validKeys);
            }
        }

        if (false !== $return = msg_set_queue($this->connection, $data)) {
            ServiceException::failedToSetQueueInformation();
        }

        return $return;

    }

    /**
     * @return int
     */
    public function getKey()
    {
        return $this->key;
    }

}