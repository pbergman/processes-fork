<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\SystemV\IPC\SharedMemory;

use PBergman\SystemV\IPC\Semaphore\Service as SemaphoreService;
use PBergman\SystemV\IPC\Semaphore\ServiceException as SemaphoreException;

/**
 * Class Service
 *
 * @package PBergman\SystemV\IPC\SharedMemory
 */
class Service
{
    protected $id;
    protected $sem;

    /**
     * @param   int               $id         A numeric shared memory segment ID
     * @param   SemaphoreService  $semaphore
     * @throws  ServiceException
     */
    public function __construct($id, SemaphoreService $semaphore = null)
    {
        if (!is_numeric($id)) {
            ServiceException::invalidKeyGiven($id);
        } else {
            $this->id  = $id;
            $this->sem = $semaphore;
        }
    }

    /**
     * helper to set lock with semaphore,
     * if is set else will return true
     *
     * @return bool
     * @throws SemaphoreException
     */
    private function lock()
    {
        if (!is_null($this->sem)) {
            $return = false;

            if (false === $this->sem->isAcquired()) {
                if (false === $return = $this->sem->acquire()) {
                    throw SemaphoreException::couldNotAcquireSemaphore();
                }
            }
        } else {
            $return = true;
        }

        return $return;
    }

    /**
     * helper free lock from semaphore,
     * if is set else will return true
     *
     * @return  bool
     * @throws  SemaphoreException
     */
    private function release()
    {
        if (!is_null($this->sem)) {
            $return = false;

            if ($this->sem->isAcquired()) {
                if (false === $return = $this->sem->release()) {
                    throw SemaphoreException::couldNotReleaseSemaphore();
                }
            }
        } else {
            $return = true;
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

    /**
     * @return \PBergman\SystemV\IPC\Semaphore\Service
     */
    public function getSem()
    {
        return $this->sem;
    }

    /**
     * @param   \PBergman\SystemV\IPC\Semaphore\Service $sem
     * @return  \PBergman\SystemV\IPC\Semaphore\Service;
     */
    public function setSem(SemaphoreService $sem)
    {
        $this->sem = $sem;
        return $this;
    }
}