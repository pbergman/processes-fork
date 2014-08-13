<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Work;

use PBergman\Fork\Container;

/**
 * Class AbstractWork
 *
 * @package PBergman\Fork\Work
 */
abstract class AbstractWork
{
    /** @var int */
    protected $ppid;
    /** @var int */
    protected $pid;
    /** @var int  */
    protected $exitCode = 0;
    /** @var int */
    protected $usage;
    /** @var int */
    protected $duration;
    /** @var bool  */
    protected $success = true;
    /** @var  string */
    protected $error;
    /** @var mixed */
    protected $result;
    /** @var  int */
    protected $timeout;
    /** @var bool */
    protected $quiet = false;

    /**
     * the main method that is called
     *
     * @param   Container  $container
     * @return  mixed
     */
    abstract public function execute(Container $container);

    /**
     * a name identifier for logs
     *
     * @return mixed
     */
    abstract public function getName();

    /**
     * set execution duration of script
     *
     * @param  int   $duration
     * @return \PBergman\Fork\Work\AbstractWork
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * returns duration from script
     *
     * @return \PBergman\Fork\Work\AbstractWork
     */
    public function getDuration()
    {
        return $this->duration;

    }

    /**
     * return parent pid from process
     *
     * @return mixed
     */
    public function getParentPid()
    {
        return $this->ppid;
    }

    /**
     * set parent pid from process
     *
     * @param   int  $pid
     * @return  \PBergman\Fork\Work\AbstractWork
     * @throws  WorkException
     */
    public function setParentPid($pid)
    {
        if (is_null($this->ppid)) {
            $this->ppid = $pid;
        } else {
            throw WorkException::singleWriteOnly('ppid');
        }

        return $this;
    }

    /**
     * return pid from process
     *
     * @return mixed
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * set pid from process
     *
     * @param   int  $pid
     * @return  \PBergman\Fork\Work\AbstractWork
     * @throws  WorkException
     */
    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * will set true is script was run without errors
     *
     * @param  bool $success
     * @return \PBergman\Fork\Work\AbstractWork
     */
    public function setSuccess($success)
    {
        $this->success = $success;

        return $this;
    }

    /**
     * set exceptions catch from running execute
     *
     * @param   string $error
     * @return  \PBergman\Fork\Work\AbstractWork
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     *
     * @return string|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * will return result from script
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param   $result
     * @return  \PBergman\Fork\Work\AbstractWork|mixed
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * return exit code from script, this will be set by ForkManager when finished
     *
     * @return int
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * sets exit code from script
     *
     * @param  int $exitCode
     * @return \PBergman\Fork\Work\AbstractWork
     */
    public function setExitCode($exitCode)
    {
        $this->exitCode = $exitCode;

        return $this;
    }

    /**
     * sets memory usage of script
     *
     * @param  int $usage
     * @return \PBergman\Fork\Work\AbstractWork
     */
    public function setUsage($usage)
    {
        $this->usage = $usage;

        return $this;
    }

    /**
     * returns memory usage of script
     *
     * @return int
     */
    public function getUsage()
    {
        return $this->usage;
    }

    /**
     * @return int|null
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * set timeout of running script in seconds
     *
     * @param   int $timeout
     * @return  \PBergman\Fork\Work\AbstractWork
     */
    public function setTimeout($timeout)
    {
        if (is_numeric($timeout)) {
            $this->timeout = $timeout;
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function isQuiet()
    {
        return $this->quiet;
    }

    /**
     * @param boolean $quiet
     */
    public function setQuiet($quiet)
    {
        $this->quiet = $quiet;
    }
}