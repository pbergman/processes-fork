<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork;

use PBergman\SystemV\IPC\Messages\Service as MessagesService;

/**
 * Class MessageQueue
 *
 * @package PBergman\Fork
 */
class MessageQueue
{
    /** @var Container */
    protected $container;
    /** @var  int */
    protected $key;

    /**
     * @param Container $container
     */
    function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return MessagesService
     */
    public function getMessageQueue()
    {
        $this->container['mess.conf.token'] = $this->key;
        return $this->container['message_queue'];
    }

    public function newInstance()
    {
        $token = ftok(__FILE__, 'm');

        while (MessagesService::exists($token)) {
            $token += 1;
        }

        $self = clone $this;
        $self->setKey($token);

        return $self;
    }

    /**
     * @return int
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param int $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
}
