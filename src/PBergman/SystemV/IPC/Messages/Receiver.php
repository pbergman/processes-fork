<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\SystemV\IPC\Messages;

/**
 * Class Receive
 *
 * @package PBergman\SystemV\IPC\Messages
 */
class Receiver
{
    /** @var resource */
    private $connection;
    /** @var int  */
    private $type;
    /** @var  int */
    private $returnedType;
    /** @var  mixed */
    private $data;
    /** @var int  */
    private $maxSize = 16384;
    /** @var bool  */
    private $unserialize = true;
    /** @var int  */
    private $flags = 0;
    /** @var  string */
    private $error;
    /** @var  int */
    private $errorCode;
    /** @var  bool */
    private $success;
    /** @var  array */
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
     * Will run command and return self
     */
    public function pull()
    {
        if (false === $this->success = @msg_receive($this->connection, $this->type, $this->returnedType, $this->maxSize, $this->data, $this->unserialize, $this->flags, $this->errorCode)) {
            $this->error  = \PBergman\SystemV\IPC\Helpers\ErrorsMapping::getMessage($this->errorCode);
        } else {
            $this->status = msg_stat_queue($this->connection);
        }

        return $this;
    }

    /**
     * @param   mixed $data
     * @return  $this
     */
    public function setData($data)
    {
        $this->data = $data;
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
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param   int $flags
     * @return  $this
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;
        return $this;
    }

    /**
     * @return int
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @param   int $maxSize
     * @return  $this
     */
    public function setMaxSize($maxSize)
    {
        $this->maxSize = $maxSize;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxSize()
    {
        return $this->maxSize;
    }

    /**
     * @return int
     */
    public function getReturnedType()
    {
        return $this->returnedType;
    }

    /**
     * @param   int $type
     * @return  $this
     */
    public function setType($type)
    {
        $this->type = $type;
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
     * @param   boolean $unserialize
     * @return  $this
     */
    public function setUnserialize($unserialize)
    {
        $this->unserialize = $unserialize;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getUnserialize()
    {
        return $this->unserialize;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return array
     */
    public function getStatus()
    {
        return $this->status;
    }

}