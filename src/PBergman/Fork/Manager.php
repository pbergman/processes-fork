<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\Fork;

use PBergman\Fork\Generator\DefaultGenerator;
use PBergman\Fork\Generator\GeneratorInterface;
use PBergman\Fork\Helper\Redis;
use PBergman\Fork\Logger\Formatter;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor as Processor;

/**
 * Class Manager
 *
 * @package PBergman\Fork
 */
class Manager
{
    const EVENT_FINISHED_WORKERS = 1;
    const EVENT_SPAWN_CHILDREN = 2;
    const EVENT_PUSH_JOBS = 3;
    const EVENT_WATCH_CHILDREN = 4;
    const EVENT_WAIT_FOR_FINISHED_CHILD = 5;
    const REDIS_PREFIX = '##MANAGER@';
    const EXIT_NORMAL = 0;
    const EXIT_TIMEOUT = 1;
    const EXIT_ERROR = 255;
    /** @var int  */
    protected $timeout_idle = 10;
    /** @var int  */
    protected $workers = 10;
    /** @var Redis  */
    protected $redis;
    /** @var \SplObjectStorage */
    protected $jobs;
    /** @var LoggerInterface  */
    protected $logger;
    /** @var GeneratorInterface  */
    protected $generator;

    /**
     * @param Redis $redis
     */
    public function __construct(Redis $redis = null, LoggerInterface $logger = null)
    {
        if (is_null($redis)) {
            $this->redis = new Redis();
        } else {
            $this->redis = $redis;
        }

        if (is_null($logger)) {
            $this->logger = new Logger('manager');
            $this->logger->pushHandler((new StreamHandler(STDOUT, Logger::DEBUG))->setFormatter(new Formatter()));
            $this->logger->pushProcessor(new Processor\PsrLogMessageProcessor());
        } else {
            $this->logger = $logger;
        }

        $this->redis->connect('127.0.0.1');
        $this->redis->setOption(Redis::OPT_PREFIX, self::REDIS_PREFIX);
        $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $this->jobs = new \SplObjectStorage();
        $this->removeCache();
        $this->generator = new DefaultGenerator();
    }

    /**
     * remove redis cache that is set within the prefix
     */
    protected function removeCache()
    {
        $keys = $this->redis->keys('*');
        $prefix = $this->redis->getOption(Redis::OPT_PREFIX);
        $this->redis->setOption(Redis::OPT_PREFIX, null);
        foreach ($keys as $key) {
            $this->redis->delete($key);
        }
        $this->redis->setOption(Redis::OPT_PREFIX, $prefix);
    }

    /**
     * add a job to stack, can be closure or a class implementing __invoke()
     *
     * @param callable $job
     */
    public function addJob(callable $job)
    {
        $this->jobs->attach($job);
        $this->jobs->rewind();
    }
    /**
     * main method to be called to start jobs,
     * will monitor children and delegate work
     *
     * @throws \RedisException
     */
    public function run()
    {
        $pids = [];
        $event = self::EVENT_SPAWN_CHILDREN;
        $signaled = [];

        do {
            switch ($event) {
                case self::EVENT_FINISHED_WORKERS:
                    if (count($pids) && $this->redis->lLen(self::parentChanelNotifier()) > 0) {
                        while (false !== $pid = $this->redis->lPop(self::parentChanelNotifier())) {
                            $this->logger->debug(sprintf('Child %s finished', $pid));
                            if (isset($pids[$pid])) {
                                $pids[$pid] = true;
                            }
                        }
                    }
                    $event++;
                    break;
                case self::EVENT_SPAWN_CHILDREN:
                    if ($this->jobs->valid() && count($pids) < $this->workers) {
                        $max = ($this->workers > count($this->jobs)) ? count($this->jobs) : $this->workers;
                        for ($i = count($pids); $i < $max; $i++) {
                            try {
                                $pid = $this->fork();
                                $this->logger->debug(sprintf('Child spawned %s [%s/%s]', $pid, count($pids) + 1, $this->workers));
                                $pids[$pid] = true;
                                $this->redis->reconnect();
                            } catch (\Exception $e) {
                                $this->logger->error(sprintf('%s @ %s(%s)', $e->getMessage(), $e->getFile(), $e->getLine()));
                                exit(self::EXIT_ERROR);
                            }
                        }
                    }
                    $event++;
                    break;
                case self::EVENT_PUSH_JOBS:
                    $available = array_keys(array_filter($pids));
                    if (!empty($available)) {
                        foreach ($available as $pid) {
                            if ($this->jobs->valid()) {
                                $this->pushJob($pid);
                            } else {
                                $this->pushJob($pid, true);
                                $signaled[] = $pid;
                            }
                            if (isset($pids[$pid])) {
                                $pids[$pid] = false;
                            }
                        }
                    }
                    $event++;
                    break;
                case self::EVENT_WATCH_CHILDREN:
                    if (!empty($signaled)) {
                        if ($this->jobs->valid() || count($this->getRunning($pids, $signaled))) {
                            foreach ($pids as $pid => $working) {
                                $this->checkExitChild($pid, $pids, $signaled, WNOHANG|WUNTRACED);
                            }
                        } else {
                            $this->checkExitChild(-1, $pids, $signaled, WUNTRACED);
                        }
                    }
                    $event++;
                    break;
                case self::EVENT_WAIT_FOR_FINISHED_CHILD:
                    if (count($this->getRunning($pids, $signaled))) {
                        try {
                            $pid = $this->redis->blPop([self::parentChanelNotifier()], 10);
                            if (!empty($pid) && isset($pids[$pid[1]])) {
                                $pids[$pid[1]] = true;
                            }
                        } catch (\RedisException $r) {};
                    }
                    $event = self::EVENT_FINISHED_WORKERS;
                    break;

            }

        } while (count($pids) > 0);
    }

    /**
     * helper to get all running pids that are not signaled for shutdown
     *
     * @param   array     $pids
     * @param   array     $signaled
     * @return  array
     */
    protected function getRunning(array $pids, array $signaled)
    {
        $running = [];

        array_walk($pids, function($v, $k) use (&$running, $signaled){
            if (!$v && !in_array($k, $signaled)) {
                $running[] = $k;
            }
        });

        return $running;
    }

    /**
     * do a check on child process(es) en output exit status
     *
     * @url http://php.net/manual/en/function.pcntl-waitpid.php
     *
     * @param int   $pid
     * @param array $pids
     * @param array $signaled
     * @param int   $options
     */
    protected function checkExitChild($pid, array &$pids, &$signaled, $options = 0)
    {
        if (0 !== $ret = pcntl_waitpid($pid, $status, $options)) {
            if ($ret === -1) {
                $this->logger->error(sprintf('[%d] %s', $pid, pcntl_strerror(pcntl_get_last_error())));
            } else {
                if (isset($pids[$ret])) {
                    unset($pids[$ret]);
                }
                if (in_array($ret, $signaled)) {
                    unset($signaled[array_search($ret, $signaled)]);
                }
                switch (true) {
                    case pcntl_wifstopped($status):
                        $this->logger->error(sprintf('Signal: %s caused this child to stop', pcntl_wstopsig($status)));
                        break;
                    case pcntl_wifsignaled($status);
                        $this->logger->error(sprintf("Signal: %s caused this child to exit", pcntl_wtermsig($status)));
                        break;
                    default:
                        $this->logger->debug(sprintf("Child %s exited with code %s", $ret, pcntl_wexitstatus($status)));
                }
            }
        }
    }

    /**
     * will push job to queue
     *
     * @param bool  $exit
     */
    protected function pushJob($pid, $exit = false)
    {
        if ($exit) {
            $this->logger->debug(sprintf('Pushing exit signal to %s%s', self::REDIS_PREFIX, $pid));
            if (false === $this->redis->publish(self::childChanelNameExit($pid, null), true)) {
                $this->logger->error($this->redis->getLastError());
                $this->redis->clearLastError();
            }
        } else {
            $this->logger->debug(sprintf('Pushing job to queue %s%s', self::REDIS_PREFIX, $pid));
            $this->jobs->rewind();
            $job = $this->jobs->current();
            $this->jobs->detach($this->jobs->current());
            $message = $this->generator->pack($job);
            if (false === $this->redis->publish(self::childChanelNameWork($pid, null), $message)) {
                $this->logger->error($this->redis->getLastError());
                $this->redis->clearLastError();
            }
        }

    }

    /**
     * fork a child process, and return the pid for
     * parent process, will start event loop for child
     *
     * @return bool|int
     * @throws \RuntimeException
     */
    protected function fork()
    {
        if (!function_exists('pcntl_fork')) {
            throw new \RuntimeException('Function pcntl_fork does not exist');
        }
        switch ($pid = pcntl_fork()) {
            case -1:
                throw new \RuntimeException('Could not fork process');
                break;
            case 0:
                $redis = $this->redis->duplicate();
                $redis->setOption(Redis::OPT_READ_TIMEOUT, $this->timeout_idle);
                unset($this->jobs, $this->workers, $this->redis);
                $this->initChild();
                try {
                    $redis->psubscribe([self::childBaseChanelName()], function(Redis $client, $pattern, $chan, $msg) {
                        $redis = $client->duplicate();
                        switch ($chan) {
                            case self::childChanelNameWork():
                                try {
                                    $work = $this->generator->unpack($msg);
                                    if (!is_null($work )) {
                                        if (is_callable($work)) {
                                            try{
                                                $work($redis, $this->logger);
                                                $this->logger->debug('Job finished');
                                            } catch (\Exception $e){
                                                $this->logger->error(sprintf('%s: %s', get_class($e), $e->getMessage()));
                                            }
                                            if (!$redis->publish(self::childChanelNameCleanup(null, null), true)) {
                                                $this->logger->error($redis->getLastError());
                                            }
                                        }  else {
                                            $this->logger->notice(sprintf('Received work but was not callable, got: "%s"', gettype($work)));
                                        }
                                    }
                                } catch (\Exception $e) {
                                    $this->logger->error(sprintf('%s @ %s(%s)', $e->getMessage(), $e->getFile(), $e->getLine()));
                                }
                                break;
                            case self::childChanelNameCleanup():
                                if (function_exists('gc_collect_cycles')) {
                                    $this->logger->debug('Cleaning up resources');
                                    gc_collect_cycles();
                                }
                                if (!$redis->lPush(self::parentChanelNotifier(), getmypid())) {
                                    $this->logger->error($redis->getLastError());
                                }
                                break;
                            case self::childChanelNameExit():
                                $this->logger->debug('Received exit signal, shutting down');
                                $client->close();
                                break;
                        }
                    });
                } catch (\RedisException $e) {
                    if ($e->getMessage() === 'read error on connection') {
                        $this->logger->notice(sprintf('Exiting after running %d sec idle', $this->timeout_idle));
                        exit(self::EXIT_TIMEOUT);
                    } else {
                        $this->logger->error($e->getMessage());
                        exit(self::EXIT_ERROR);
                    }
                }
                exit(self::EXIT_NORMAL);
                break;
            default:
                return $pid;

        }
    }

    /**
     * wrapper to get the cleanup chanel name
     *
     * @param   null|int    $pid
     * @param   null|string  $prefix
     * @return  string
     */
    protected static function childChanelNameCleanup($pid = null, $prefix = self::REDIS_PREFIX)
    {
        return self::childBaseChanelName($prefix, 'CLEANUP', $pid);
    }

    /**
     * wrapper to get the work chanel name
     *
     * @param   null|int    $pid
     * @param   null|string  $prefix
     * @return  string
     */
    protected static function childChanelNameWork($pid = null, $prefix = self::REDIS_PREFIX)
    {
        return self::childBaseChanelName($prefix, 'JOB', $pid);
    }

    /**
     * wrapper to get the exit chanel name
     *
     * @param   null|int     $pid
     * @param   null|string  $prefix
     * @return  string
     */
    protected static function childChanelNameExit($pid = null, $prefix = self::REDIS_PREFIX)
    {
        return self::childBaseChanelName($prefix, 'EXIT', $pid);
    }

    /**
     * will generate a chanel name base on given params
     *
     * @param null|string   $prefix
     * @param string        $chanel
     * @param null          $pid
     * @return string
     */
    protected static function childBaseChanelName($prefix = null, $chanel = '*', $pid = null)
    {
        if (!$pid) {
            $pid = getmypid();
        }

        return sprintf('%sCHILD#%s@%s', $prefix, $pid, $chanel);
    }

    /**
     * wrapper to get the parent notifier chanel name
     *
     * @param   null $prefix
     * @return  string
     */
    protected static function parentChanelNotifier($prefix = null)
    {
        return sprintf('%sPARENT#NOTIFIER', $prefix);
    }


    /**
     * will disable error output and delegate errors on exit
     */
    protected function initChild()
    {
        error_reporting(0);
        register_shutdown_function(function(){
            $error = error_get_last();
            if ($error['type'] === ($error['type'] & (E_ERROR|E_COMPILE_ERROR|E_CORE_ERROR|E_USER_ERROR))) {
                $this->logger->error(sprintf('%s (%s)', $error['message'], $error['line']));
                exit(self::EXIT_ERROR);
            }
        });
    }

    /**
     * @param  int $workers
     * @return $this;
     */
    public function setWorkers($workers)
    {
        $this->workers = $workers;
        return $this;
    }

    /**
     * @param  int $timeout_idle
     * @return $this;
     */
    public function setTimeoutIdle($timeout_idle)
    {
        $this->timeout_idle = $timeout_idle;
        return $this;
    }

    /**
     * @param  GeneratorInterface $generator
     * @return $this;
     */
    public function setGenerator(GeneratorInterface $generator)
    {
        $this->generator = $generator;
        return $this;
    }
}
