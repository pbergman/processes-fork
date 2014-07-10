<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\ProcessesFork;

interface ForkJobInterface
{

    /**
     * the main method that is called
     *
     * @return mixed
     */
    public function execute();

    /**
     * will run after execute to set some debug params
     *
     * @return mixed
     */
    public function postExecute();

    /**
     * returns duration from script
     *
     * @return int
     */
    public function getDuration();

    /**
     * return exit code from script, this will be set by ForkManager when finished
     *
     * @return int
     */
    public function getExitCode();

    /**
     * sets exit code from script
     *
     * @param  int  $exitCode
     * @return $this
     */
    public function setExitCode($exitCode);

    /**
     * returns usage of script
     *
     * @return int
     */
    public function getUsage();


    /**
     * return pid from child process
     *
     * @return mixed
     */
    public function getPid();

    /**
     * set pid from child process
     *
     * @param  int  $pid
     * @return $this
     */
    public function setPid($pid);

    /**
     * a name identifier for logs
     *
     * @return mixed
     */
    public function getName();

    /**
     * set id to reference to object storage
     *
     * @param  int  $id
     * @return $this
     */
    public function setId($id);


    /**
     * @return int
     */
    public function getId();

    /**
     * @return bool
     */
    public function isSuccess();

    /**
     * set exceptions catch from running execute
     *
     * @param   string $error
     * @return  $this
     */
    public function setError($error);

    /**
     * @return string|null
     */
    public function getError();

    /**
     * @param  bool $success
     * @return $this
     */
    public function setSuccess($success);

    /**
     * @return mixed
     */
    public function getResult();

    /**
     * @param   $result
     * @return  $this|mixed
     */
    public function setResult($result);

}