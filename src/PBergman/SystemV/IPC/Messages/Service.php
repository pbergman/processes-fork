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
    private $queue;

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

        $this->queue = msg_get_queue($key, $perms);
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
        if (false === $return = msg_remove_queue($this->queue)) {
            throw ServiceException::failedToRemove($this->queue);
        } else {
            return $return;
        }
    }

    /**
     * Sends a message of type $type (which MUST be greater than 0) to the message queue specified by queue.
     *
     * @param   int     $type
     * @param   mixed   $data
     * @param   bool    $serialize
     * @param   bool    $blocking
     *
     * @return  bool
     * @throws ServiceException
     */
    public function send($data, $type, $serialize = true, $blocking  = true)
    {
        if (!is_numeric($type) ||  $type < 0) {
            throw ServiceException::invalidMessageType($type);
        }

        if (false === $return = @msg_send($this->queue, $type, $data, $serialize, $blocking, $error)) {
            throw ServiceException::failedToSend($error);
        } else {
            return $return;
        }
    }


    /**
     * Receive a message from a message queue
     *
     * @param int   $type
     * @param int   $msgtype
     * @param int   $maxsize
     * @param mixed $data
     * @param bool  $unserialize
     * @param int   $flags
     * @param null  $error
     *
     *
     * Flag values for msg_receive
     *
     * MSG_IPC_NOWAIT	If there are no messages of the desiredmsgtype, return immediately and do not wait. The
     *                  function will fail and return an integer value corresponding to MSG_ENOMSG.
     * MSG_EXCEPT	    Using this flag in combination with a desiredmsgtype greater than 0 will cause the function
     *                  to receive the first message that is not equal to desiredmsgtype.
     * MSG_NOERROR	    If the message is longer than maxsize, setting this flag will truncate the message to
     *                  maxsize and will not signal an error.
     *
     *
     * @return bool
     * @throws ServiceException
     */
    public function receive($type, &$msgtype, $maxsize, &$data, $unserialize = true, $flags = 0, &$error = null)
    {
        if (!is_numeric($type)) {
            throw ServiceException::invalidMessageType($type);
        }

        return msg_receive($this->queue, $type, $msgtype, $maxsize, $data, $unserialize, $flags, $error);
    }


    /**
     * returns the message queue meta data for the message queue specified by the queue.
     * This is useful, for example, to determine which process sent the message that was just receive
     *
     * @return array
     */
    public function status()
    {
        return msg_stat_queue($this->queue);
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

        if (false !== $return = msg_set_queue($this->queue, $data)) {
            ServiceException::failedToSetQueueInformation();
        } else {
            return $return;
        }

    }

}