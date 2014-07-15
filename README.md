processes-fork
=========

a helper class to control, debug and manage process forks

Usage
=====

Simple example:

```php
<?php

use PBergman\Fork\Work\AbstractWork;
use PBergman\Fork\Manager;
use PBergman\Fork\Helpers\OutputHelper as OutputHandler;

class job extends AbstractWork
{
    /**
     * add timeout for fun
     */
    public function __construct()
    {
        $this->setTimeout(2);
    }

    /**
     * the main method that is called
     *
     * @param  OutputHandler    $output
     * @return mixed
     */
    public function execute(OutputHandler $output)
    {
        $sleep = rand(2, 10);
        $output->debug(sprintf("sleeping %s", $sleep), $this->getPid(), OutputHandler::PROCESS_CHILD);
        sleep($sleep);
    }

    /**
     * a name identifier for logs
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->getPid();
    }
}

$stack = array();

for($i =0 ; $i < 10; $i++){
    $stack[] = new Job();
}

$fm = new Manager();
$fm->setWorkers(5)      // add 5 process forks
    ->setJobs($stack)   // add work
    ->run();            // start process

```

This will give a output like :
<pre>
2014-07-15 12:22:32 [CHILD  ] [28667 ] Starting:  <br>
2014-07-15 12:22:32 [CHILD  ] [28668 ] Starting:  <br>
2014-07-15 12:22:32 [CHILD  ] [28667 ] sleeping 3 <br>
2014-07-15 12:22:32 [CHILD  ] [28669 ] Starting:  <br>
2014-07-15 12:22:32 [CHILD  ] [28668 ] sleeping 3 <br>
2014-07-15 12:22:32 [CHILD  ] [28669 ] sleeping 4 <br>
2014-07-15 12:22:32 [CHILD  ] [28670 ] Starting:  <br>
2014-07-15 12:22:32 [CHILD  ] [28670 ] sleeping 5 <br>
2014-07-15 12:22:32 [CHILD  ] [28671 ] Starting:  <br>
2014-07-15 12:22:32 [CHILD  ] [28671 ] sleeping 9 <br>
2014-07-15 12:22:34 [ERROR  ] [28667 ] E_USER_ERROR: timeout exceeded: 2 second(s) on line 147 in file /var/www/processes-fork/src/PBergman/Fork/Work/Controller.php <br>
2014-07-15 12:22:34 [ERROR  ] [28667 ] #0 /var/www/processes-fork/src/PBergman/Fork/Helpers/ErrorHelper.php(62): PBergman\Fork\Helpers\ErrorHelper->(printBackTrace) <br>
2014-07-15 12:22:34 [ERROR  ] [28667 ] #1 (): PBergman\Fork\Helpers\ErrorHelper->(PBergman\Fork\Helpers\{closure}) <br>
2014-07-15 12:22:34 [ERROR  ] [28667 ] #2 /var/www/processes-fork/src/PBergman/Fork/Work/Controller.php(147): (trigger_error) <br>
2014-07-15 12:22:34 [ERROR  ] [28667 ] #3 /var/www/processes-fork/src/PBergman/Fork/Work/Controller.php(67): PBergman\Fork\Work\Controller->(PBergman\Fork\Work\{closure}) <br>
2014-07-15 12:22:34 [ERROR  ] [28667 ] #4 /var/www/processes-fork/src/PBergman/Fork/Manager.php(76): PBergman\Fork\Work\Controller->(run) <br>
2014-07-15 12:22:34 [ERROR  ] [28667 ] #5 /var/www/processes-fork/test.php(56): PBergman\Fork\Manager->(run) <br>
2014-07-15 12:22:34 [CHILD  ] [28667 ] Finished: 28667 (0.47 MB/2.86 s) <br>
</pre>

Methods:
=======

## (PBergman\Fork)Manager:
    +  getJobs()           will return the finished jobs that were added by setJobs/addJob (as SplObjectStorage)
    +  addJob()            will add job to queue, has to be a class extending AbstractWork
    +  setJobs()           will set array of jobs each have extending AbstractWork
    +  setWorkers()        will set set amount of workers that will be spawned (default 1)
    +  run()               will start the fork process

