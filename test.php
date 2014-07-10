<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 */

require_once 'vendor/autoload.php';

//use PBergman\SystemFork\Semaphore\SharedMemory;
//use PBergman\SystemFork\Semaphore\Semaphore;

//$token = ftok('/home/philip/Projects/Fork/composer.json', 'c');//ftok(__FILE__, 'c');
//$sem   = new Semaphore($token, 1, 0600, 1);
//$shm   = new SharedMemory($token, $sem);
//
//$shm->put(1, 1);
//
//$children = array();
//$parent   = false;
//for ($i = 1; $i <= 10; ++$i) {
//
//    $pid = pcntl_fork();
//
//    if ($pid == -1) {
//        die('could not fork');
//    } else if ($pid) {
//        $parent = true;
//        $children[] = $pid;
//    } else {
//        $s = rand(1, 5);
//        sleep($s);
//        $c = $shm->get(1);
//        $c++;
//        $shm->put(1, $c);
//        printf("[%s] In child %s sleeping %s\n", posix_getpid(), $i, $s);
//        exit;
//    }
//}
//
//if ($parent) {
//    while(count($children) > 0) {
//        foreach($children as $k => $pid) {
//            if (pcntl_waitpid($pid, $status, WNOHANG)) {
//                printf("[%s] child finished counter: %s\n", $pid, $shm->get(1));
//                unset($children[$k]);
//            }
//        }
//    }
//}
//
////$shm->put(11111, 'blssssssdasdsd adsa asdsad s ad dsa dasd sadsa dsadsddd');
////
////var_dump($shm->get(11111));
////
//var_dump($shm->get(1));
//$shm->flush();
//$sem->remove();


use PBergman\ProcessesFork\AbstractForkJob;
use PBergman\ProcessesFork\ForkManager;

class job extends AbstractForkJob
{

    /**
     * the main method that is called
     *
     * @return mixed
     */
    public function execute()
    {
        $sleep = rand(1,3);
//        aaa();
        aaa();
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

$fm = new ForkManager();
$fm->setWorkers(5)
   ->setJobs($stack)
   ->run();
print_r($fm->getJobs());