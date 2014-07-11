<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 */

require_once 'vendor/autoload.php';

use PBergman\Fork\Work\AbstractWork;
use PBergman\Fork\Manager;

class job extends AbstractWork
{

    /**
     * the main method that is called
     *
     * @return mixed
     */
    public function execute()
    {
        $sleep = rand(5,10);
//       aaa;
//        aaa();
//        printf("sleeping %s\n", $sleep);
        sleep($sleep);
    }

    /**
     * returns duration from script
     * @return int
     */
    public function getDuration()
    {
        return 10;
    }

    /**
     * returns data that needs to be accessible by parent
     * @return mixed
     */
    public function getData()
    {
        return array();
    }

    /**
     * a name identifier for logs
     *
     * @return mixed
     */
    public function getName()
    {
        return rand(100,500);//$this->getPid();
    }
}

$stack = array();

for($i =0 ; $i < 10; $i++){
    $stack[] = new Job();
}

$fm = new Manager();
$fm->setWorkers(5)
    ->setJobs($stack)
    ->run();
print_r($fm->getJobs());