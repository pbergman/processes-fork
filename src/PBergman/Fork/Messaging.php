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
class Messaging extends MessagesService
{
    /**
     * if no token given will get new
     * token that is not used and
     * creates a new message queue
     *
     * @param int $token
     */
    function __construct($token = null)
    {
        if (is_null($token)) {
            $token = ftok(__FILE__, 'm');
            // Find new token, if token is used
            while (parent::exists($token)) {
                $token += 1;
            }
        }

        parent::__construct($token);
    }

    /**
     * will return a different message queue identifier, but
     * both identifiers access the same underlying message queue
     *
     *
     * @return Messaging
     */
    public function newInstance()
    {
        return new self($this->getKey());
    }
}
