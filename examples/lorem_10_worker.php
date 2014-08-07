<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
include_once dirname(__FILE__ ) . '/../vendor/autoload.php';

use PBergman\Fork\Container;
use PBergman\Fork\Work\AbstractWork;
use PBergman\Fork\ForkManager;

class TextFetcher extends AbstractWork
{
    /**
     * the main method that is called
     *
     * @param  Container $container
     * @return mixed
     */
    public function execute(Container $container)
    {
        $data = json_decode(file_get_contents('http://www.randomtext.me/api/lorem/ul-5/25-45'));
        $this->setResult($data->text_out);
        sleep(2);
    }

    /**
     * a name identifier for logs
     *
     * @return mixed
     */
    public function getName()
    {
        return posix_getpid();
    }
}

$stack = array();

for ($i = 0; $i < 20; $i++) {
    $stack[] = new TextFetcher();
}


$forkManager = new ForkManager();
$forkManager->setWorkers(10)
    ->setMaxSize(128000000)
    ->setJobs($stack)
    ->run();

/** @var \SplObjectStorage $result */
$result = $forkManager->getJobs();
$result->rewind();

while ($result->valid()) {
    /** @var AbstractWork $object */
    $object = $result->current();
    echo sprintf("GOT[%s] **********************\n\n", $result->key());
    echo $object->getResult();
    echo "\n\n";
    $result->next();
}
