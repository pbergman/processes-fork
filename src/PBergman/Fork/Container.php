<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork;

use PBergman\Container\Service as BaseContainer;
use PBergman\SystemV\IPC\Semaphore\Service as SemaphoreService;
use PBergman\SystemV\IPC\Messages\Service  as MessagesService;
use PBergman\Fork\Helper\ExitHelper;
use PBergman\Fork\Helper\IdentifierHelper;
use PBergman\Fork\Helper\SignalHelper;
use PBergman\Fork\Output\Output;

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
        parent::addArray($this->getDependencies());
    }

    /**
     * @return array
     */
    protected function getDependencies()
    {
        return array(
            'helper.identifier' => function() {
                    return new IdentifierHelper();
                },
            'helper.exit'       => function(self $c) {
                    return new ExitHelper($c['helper.identifier']);
                },
            'helper.signal'     => function(){
                    return new SignalHelper();
                },
            'output'            => function(){
                    return new Output();
                },
            'semaphore'         => parent::getFactory()->service(function(self $c){
                    return new SemaphoreService($c['sem.conf.token'], $c['sem.conf.workers'], 0660, false);
                }),
            'message_queue'     => parent::getFactory()->service(function(self $c){
                    return new MessagesService($c['mess.conf.token'], 0600);
                }),
        );
    }

}