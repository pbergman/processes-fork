processes-fork 2.0
=========

a small wrapper to dispatch work to workers (child processes), it uses opis/closure to serialize closures and
redis (subscribe/publish and list methods) to distribute the work. This works bit different then the old one
because this one will fork children en send work to them instead of forking new children for every job.

###Usage

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


