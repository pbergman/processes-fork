<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Semaphore;

/**
 * Class SharedMemory
 *
 * @property    Semaphore     sem
 * @package     PBergman\Semaphore
 */
class SharedMemory
{
    protected $id;
    protected $sem;

    /**
     * @param int       $id         A numeric shared memory segment ID
     * @param Semaphore $semaphore
     */
    public function __construct($id, Semaphore $semaphore)
    {
        if (!is_numeric($id)) {
            SharedMemoryException::invalidKeyGiven($id);
        } else {
            $this->id  = $id;
            $this->sem = $semaphore;
        }
    }

    /**
     * helper to set lock with semaphore
     *
     * @return bool
     * @throws SemaphoreException
     */
    private function lock()
    {
        $return = false;

        if (false === $this->sem->isAcquired()) {
            if (false === $return = $this->sem->acquire()) {
                throw SemaphoreException::couldNotAcquireSemaphore();
            }
        }

        return $return;
    }

    /**
     * helper free lock from semaphore
     *
     * @return  bool
     * @throws  SemaphoreException
     * @throws  SemaphoreException
     */
    private function release()
    {
        $return = false;

        if ($this->sem->isAcquired()) {
            if (false === $return = $this->sem->release()) {
                throw SemaphoreException::couldNotReleaseSemaphore();
            }
        }

        return $return;
    }

    /**
     * will get semaphore, if not exists will return false
     * or on failure throws SemaphoreException
     *
     * @param   int $key
     * @return  mixed
     * @throws  SemaphoreException
     */
    public function get($key)
    {
        $shmid  = $this->attach(null, null);
        $return = @shm_get_var($shmid, $key);
        $this->detach($shmid);
        return $return;
    }

    /**
     * Inserts or updates a variable in shared memory
     *
     * @param   int     $key
     * @param   string  $value
     *
     * @return bool
     *
     * @throws SemaphoreException
     * @throws SharedMemoryException
     */
    public function put($key, $value)
    {
        if ($this->has($key)) {
            $this->remove($key);
        }

        $shmid  = $this->attach($this->strlen(serialize($value)), 0600);
        $return = @shm_put_var($shmid, $key, $value);
        $this->detach($shmid);

        return $return;

    }

    /**
     * Creates or open a shared memory segment
     *
     * @param   null    $memsize
     * @param   int     $perm
     * @param   bool    $lock       if true will set semaphore lock
     *
     * @return  resource
     * @throws  SemaphoreException
     */
    public function attach($memsize = null, $perm = 0666, $lock = true)
    {
        if ($lock) {
            $this->lock();
        }

        if (is_null($memsize)) {
            $return = shm_attach($this->id);
        } else {
            $return = shm_attach($this->id, $memsize, $perm);
        }

        if (false === $return) {
            /** release lock */
            $this->release();
            throw SemaphoreException::couldNotCreateIdentifier();
        } else {
            return $return;
        }
    }

    /**
     * Disconnects from shared memory segment
     *
     * @param   $id
     * @param   bool $release   if true will release semaphore lock
     * @return  bool
     */
    public function detach($id, $release = true)
    {
        if ($release) {
            $this->release();
        }

        return shm_detach($id);
    }

    /**
     * Check whether a specific entry exists
     *
     * @param  int  $key
     * @return bool
     */
    public function has($key)
    {
        $shmid  = $this->attach(null, null);
        $return = @shm_has_var($shmid, $key);
        $this->detach($shmid);
        return $return;
    }

    /**
     * Removes a variable from shared memory
     *
     * @param   int $key
     * @return  bool
     */
    public function remove($key)
    {
        $shmid  = $this->attach(null, null);
        $return = @shm_remove_var($shmid, $key);
        $this->detach($shmid);
        return $return;
    }

    /**
     * Removes shared memory from Unix systems
     *
     * @return  bool
     */
    public function flush()
    {
        $shmid  = $this->attach(null, null);
        $return = @shm_remove($shmid);
        $this->detach($shmid);
        return $return;
    }

    /**
     * will try to return mb_strlen if possible else strlen
     *
     * @param $string
     * @return int
     */
    protected function strlen($string)
    {
        $return = null;

        if (function_exists('mb_strlen')) {

            if (false !== $encoding = mb_detect_encoding($string)) {
                $return = mb_strlen($string, $encoding);
            }
        }

        if (is_null($return)) {
            $return = strlen($string);
        }

        return $return;
    }

}
