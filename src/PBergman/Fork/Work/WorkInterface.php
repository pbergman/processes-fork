<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace PBergman\Fork\Work;

/**
 * Interface WorkInterface
 *
 * @package PBergman\Fork\Work
 */
interface WorkInterface
{

    /**
     * the main method that is called
     *
     * @return mixed
     */
    public function execute();

    /**
     * set execution duration of script
     *
     * @param  int   $duration
     * @return $this
     */
    public function setDuration($duration);

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
     * sets memory usage of script
     *
     * @param  int $usage
     * @return $this
     */
    public function setUsage($usage);

    /**
     * returns memory usage of script
     *
     * @return int
     */
    public function getUsage();


    /**
     * return parent pid from process
     *
     * @return mixed
     */
    public function getParentPid();

    /**
     * set parent pid from process
     *
     * @param  int  $pid
     * @return $this
     */
    public function setParentPid($pid);

    /**
     * return pid from process
     *
     * @return mixed
     */
    public function getPid();

    /**
     * set pid from process
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
     * will set true is script was run without errors
     *
     * @param  bool $success
     * @return $this
     */
    public function setSuccess($success);

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
     * will return result from script
     *
     * @return mixed
     */
    public function getResult();

    /**
     * @param   $result
     * @return  $this|mixed
     */
    public function setResult($result);

}