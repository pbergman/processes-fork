<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\SystemV\IPC\Messages;

/**
 * Class Sender
 *
 * @package PBergman\SystemV\IPC\Messages
 */
class Sender
{
    /** @var resource  */
    private $connection;
    /** @var int */
    private $type;
    /** @var mixed  */
    private $data;
    /** @var bool  */
    private $serialize = true;
    /** @var bool  */
    private $blocking = true;
    /** @var string */
    private $error;
    /** @var int */
    private $errorCode;
    /** @var bool */
    private $success;
    /** @var array  */
    private $status;

    /**
     * @param   resource $connection
     * @throws  ServiceException
     */
    public function __construct($connection)
    {
        if (is_resource($connection) && get_resource_type($connection) === Service::RESOURCE_TYPE) {
            $this->connection = $connection;
        } else {
            throw ServiceException::invalidResource();
        }
    }

    /**
     * @return $this
     */
    public function push()
    {
        if(false === $this->success = @msg_send($this->connection, $this->type, $this->data, $this->serialize, $this->blocking, $this->errorCode)) {
            $this->error  = posix_strerror($this->errorCode);
        } else {
            $this->status = msg_stat_queue($this->connection);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param   int     $type
     * @return  $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param   mixed   $data
     * @return  $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getSerialize()
    {
        return $this->serialize;
    }

    /**
     * @param   boolean $serialize
     * @return  $this
     */
    public function setSerialize($serialize)
    {
        $this->serialize = $serialize;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getBlocking()
    {
        return $this->blocking;
    }

    /**
     * @param   boolean $blocking
     * @return  $this
     */
    public function setBlocking($blocking)
    {
        $this->blocking = $blocking;
        return $this;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * @return array
     */
    public function getStatus()
    {
        return $this->status;
    }


}
 