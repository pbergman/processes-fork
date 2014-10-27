<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork;

use PBergman\Container\Service as BaseContainer;
use PBergman\SystemV\IPC\Messages\Service  as MessagesService;

/**
 * wrapper for container
 *
 * Class Container
 */
class Container extends BaseContainer
{
    /**
     * @magic __construct
     */
    function __construct()
    {
        parent::__construct();
        parent::addArray(array_merge(
            $this->getParameters(),
            $this->getMethods()
        ));
    }


    /**
     * returning parameters for container
     *
     * @return array
     */
    protected function getParameters()
    {
        return array(
            'output.class'             => 'PBergman\Fork\Output\Output',
            'helper.signal.class'      => 'PBergman\Fork\Helper\SignalHelper',
            'helper.identifier.class'  => 'PBergman\Fork\Helper\IdentifierHelper',
            'helper.exit.class'        => 'PBergman\Fork\Helper\ExitHelper',
            'semaphore.class'          => 'PBergman\SystemV\IPC\Semaphore\Service',
            'messaging.class'          => 'PBergman\Fork\Messaging',
        );
    }

    /**
     * returning methods for this container
     *
     * @return array
     */
    protected function getMethods()
    {
        return array(
            'helper.identifier' => function(self $c) {
                return new $c['helper.identifier.class'];
            },
            'helper.exit'       => function(self $c) {
                return new $c['helper.exit.class']($c['helper.identifier']);
            },
            'helper.signal'     => function(self $c){
                return new $c['helper.signal.class'];
            },
            'output'            => function(self $c){
                return new $c['output.class'];
            },
            'semaphore'         => parent::getFactory()->service(function(self $c){
                return new $c['semaphore.class']($c['sem.conf.token'], $c['sem.conf.workers'], 0660, false);
            }),
            'messaging'          => parent::getFactory()->service(function(self $c){
                return new $c['messaging.class'];
            }),
        );
    }

}