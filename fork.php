<?php
 /**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

require 'vendor/autoload.php';

$m = new \PBergman\Fork\Manager();

$m->setWorkers(10);

for ($i = 0; $i < 25; $i++) {
    $foo = rand(1,8);
    $m->addJob(function($redis, $logger) use ($foo) {
        $logger->info('sleeping ' . $foo);
        sleep($foo);
    });
}

$m->run();