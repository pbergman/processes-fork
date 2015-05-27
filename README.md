processes-fork 2.0
=========

a small wrapper to dispatch work to workers (child processes), it uses opis/closure to serialize closures and
redis (subscribe/publish and list methods) to distribute the work. This works bit different then the old one
because this one will fork children en send work to them instead of forking new children for every job.

###Usage

```
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

```

will output something like
 
```
[2015-05-27 18:16:52] [10663] manager.DEBUG: Child spawned 10665 [1/10]  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Child spawned 10666 [2/10]  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Child spawned 10667 [3/10]  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Child spawned 10668 [4/10]  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Child spawned 10669 [5/10]  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Child spawned 10670 [6/10]  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Child spawned 10671 [7/10]  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Child spawned 10672 [8/10]  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Child spawned 10673 [9/10]  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Child spawned 10674 [10/10]  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Pushing job to queue ##MANAGER@10665  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Pushing job to queue ##MANAGER@10666  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Pushing job to queue ##MANAGER@10667  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Pushing job to queue ##MANAGER@10668  
[2015-05-27 18:16:52] [10666] manager.INFO: sleeping 6  
[2015-05-27 18:16:52] [10663] manager.DEBUG: Pushing job to queue ##MANAGER@10669  
[2015-05-27 18:16:52] [10665] manager.INFO: sleeping 8  
[2015-05-27 18:16:52] [10667] manager.INFO: sleeping 5  
[2015-05-27 18:16:52] [10668] manager.INFO: sleeping 1  

...........

[2015-05-27 18:17:07] [10663] manager.DEBUG: Child 10672 exited with code 0  
[2015-05-27 18:17:07] [10671] manager.DEBUG: Job finished  
[2015-05-27 18:17:07] [10671] manager.DEBUG: Cleaning up resources  
[2015-05-27 18:17:07] [10663] manager.DEBUG: Pushing exit signal to ##MANAGER@10671  
[2015-05-27 18:17:07] [10671] manager.DEBUG: Received exit signal, shutting down  
[2015-05-27 18:17:09] [10674] manager.DEBUG: Job finished  
[2015-05-27 18:17:09] [10674] manager.DEBUG: Cleaning up resources  
[2015-05-27 18:17:09] [10663] manager.DEBUG: Pushing exit signal to ##MANAGER@10674  
[2015-05-27 18:17:09] [10663] manager.DEBUG: Child 10669 exited with code 0  
[2015-05-27 18:17:09] [10674] manager.DEBUG: Received exit signal, shutting down  
[2015-05-27 18:17:09] [10663] manager.DEBUG: Child 10671 exited with code 0  
[2015-05-27 18:17:09] [10663] manager.DEBUG: Child 10674 exited with code 0  

 
```

###Methods

##__construct(<PBergman\Fork\Helper\Redis> $redis = null, <Psr\Log\LoggerInterface> $logger = null)

constructor

##run()

will start dispatching work to children, is blocking till all work is done

##setWorkers(<int> $workers)

set the amount of workers to start, will shrink to size of jobs if there are more workers than work

##setTimeoutIdle(<int> $timeout_idle)

set time out that a child will run idle (waiting for new work)

##setGenerator(<PBergman\Fork\Generator\GeneratorInterface> $generator)

set the generator, that is used to convert data to send over the redis message queue

##addJob(<callable> $job)

add work to for child process, should be callable so closure or a class implementing __invoke()

