<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\ProcessesFork;

abstract class AbstractForkJob implements ForkJobInterface
{
    protected $exitCode;
    protected $usage;
    protected $duration;
    protected $pid;
    protected $id;
    protected $success = true;
    protected $error;
    protected $result;

    /**
     * constructor will set start time
     */
    public function __construct()
    {
        $this->duration = microtime(true);
    }


    /**
     * will run after execute to set some debug params
     *
     * @return mixed
     */
    public function postExecute()
    {
        $this->usage    = memory_get_usage();
        $this->duration = (microtime(true) - $this->duration);
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
     * @param  $exitCode
     * @return $this
     */
    public function setExitCode($exitCode)
    {
        $this->exitCode = $exitCode;
        return $this;
    }


    /**
     * return pid from child process
     *
     * @return mixed
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * set pid from child process
     *
     * @param  int  $pid
     * @return $this
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * set id to reference to object storage
     *
     * @param  int  $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * returns usage of script
     *
     * @return int
     */
    public function getUsage()
    {
        return $this->usage;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * @param  bool $success
     * @return $this
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
     * @return  $this
     */
    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param   $result
     * @return $this|mixed
     */
    public function setResult($result)
    {
        $this->result;
        return $this;
    }


}