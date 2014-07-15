<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 */

require_once 'vendor/autoload.php';

use PBergman\Fork\Work\AbstractWork;
use PBergman\Fork\Manager;
use PBergman\Fork\Helpers\OutputHelper as OutputHandler;


class job extends AbstractWork
{

//    public function __construct()
//    {
//        $this->setTimeout(2);
//    }

    /**
     * the main method that is called
     *
     * @param  OutputHandler    $output
     * @return mixed
     */
    public function execute(OutputHandler $output)
    {

        $sleep = rand(1,5);
//       aaa;
//        aaa();
//        $output->write(sprintf("sleeping %s", $sleep));
        sleep($sleep);
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