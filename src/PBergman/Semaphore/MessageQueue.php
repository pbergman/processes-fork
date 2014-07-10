<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Semaphore;

/**
 * Class MessageQueue
 *
 * @package PBergman\Semaphore
 */
class MessageQueue
{
    private $queue;

    /**
     * will initialize message queue
     *
     * @param   int   $key
     * @param   int   $perms
     * @throws  MessageQueueException
     */
    public function __construct($key, $perms = 0666)
    {
        if (!is_numeric($key)) {
            throw MessageQueueException::invalidKeyGiven($key);
        }

        $this->queue = msg_get_queue($key, $perms);
    }

    /**
     * check if message queue exists
     *
     * @param   int $key
     * @return  bool
     * @throws  MessageQueueException
     */
    public static function exists($key)
    {
        if (!is_numeric($key)) {
            throw MessageQueueException::invalidKeyGiven($key);
        }

        return msg_queue_exists($key);
    }

    /**
     * destroys the message queue specified by the queue. Only use this function
     * when all processes have finished working with the message queue and you
     * need to release the system resources held by it.
     *
     * @return bool
     * @throws MessageQueueException
     */
    public function remove()
    {
        if (false === $return = msg_remove_queue($this->queue)) {
            throw MessageQueueException::failedToRemove($this->queue);
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
     * @throws MessageQueueException
     */
    public function send($data, $type, $serialize = true, $blocking  = true)
    {
        if (!is_numeric($type) ||  $type < 0) {
            throw MessageQueueException::invalidMessageType($type);
        }

        if (false === $return = @msg_send($this->queue, $type, $data, $serialize, $blocking, $error)) {
            throw MessageQueueException::failedToSend($error);
        } else {
            return $return;
        }
    }
//
//    /**
//     * @param   int    $type            if left to null will set to relieved type of first message in queue
//     * @param   int    $maxsize
//     * @param   bool   $unserialize
//     * @param   int    $flags
//     * @return  bool
//     * @throws  MessageQueueException
//     * @throws  MessageQueueException
//     */
//    public function receive($type = 0,  $maxsize = 10000, $unserialize = true, $flags  = 0)
//    {
//        if (!is_numeric($type) ||  $type < 0) {
//            throw MessageQueueException::invalidMessageType($type);
//        }
//
//        if (false !==  $return = msg_receive($this->queue, $type, $msgtype, $maxsize, $data, $unserialize, $flags, $error)) {
//
//            $return = array(
//                'type'    => $msgtype,
//                'data'    => $data,
//            );
//
//        } else {
//            throw MessageQueueException::failedToReceive($error);
//        }
//
//        return $return;
//    }

    public function receive($type, &$msgtype, $maxsize, &$data, $unserialize = true, $flags = 0, &$error = null)
    {
        if (!is_numeric($type) ||  $type < 0) {
            throw MessageQueueException::invalidMessageType($type);
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
                MessageQueueException::invalidSetField($key, $validKeys);
            }
        }

        if (false !== $return = msg_set_queue($this->queue, $data)) {
            MessageQueueException::failedToSetQueueInformation();
        } else {
            return $return;
        }

    }

}