<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\SystemV\IPC\Semaphore;

/**
 * Class Service
 *
 * @package PBergman\SystemV\IPC\Semaphore
 */
class Service
{
    private $id;
    private $acquired = false;

    /**
     * Creates a new Semaphore
     *
     * @param int   $key
     * @param int   $max_acquire    The number of processes that can acquire the semaphore simultaneously is set to max_acquire
     * @param int   $perm           The semaphore permissions. Actually this value is set only if the process finds it is the only process currently attached to the semaphore.
     * @param int   $auto_release   Specifies if the semaphore should be automatically released on request shutdown.
     *
     * @throws ServiceException
     */
    public function __construct($key, $max_acquire = 1, $perm = 0666, $auto_release = 1)
    {
        if (!is_numeric($key)) {
            throw ServiceException::invalidKeyGiven($key);
        }

        if (false === $this->id = sem_get($key, $max_acquire, $perm, $auto_release)) {
            throw ServiceException::couldNotCreateIdentifier();
        }
    }

    /**
     * sem_acquire() blocks (if necessary) until the semaphore can be acquired. A process
     * attempting to acquire a semaphore which it has already acquired will block forever
     * if acquiring the semaphore would cause its maximum number of semaphore to be exceeded.
     *
     * After processing a request, any semaphores acquired by the process but not explicitly
     * released will be released automatically and a warning will be generated.
     *
     * @return bool
     */
    public function acquire()
    {
        if (false !== $return = sem_acquire($this->id)) {
            $this->acquired = true;
        }

        return $return;
    }

    /**
     * sem_release() releases the semaphore if it is currently acquired
     * by the calling process, otherwise a warning is generated.
     *
     * After releasing the semaphore, sem_acquire() may be called to re-acquire it.
     *
     * @return bool
     */
    public function release()
    {
        if (false !== $return = sem_release($this->id)) {
            $this->acquired = false;
        }

        return $return;
    }

    /**
     * sem_remove() removes the given semaphore.
     *
     * After removing the semaphore, it is no longer accessible.
     *
     * @return bool
     */
    public function remove()
    {
        /** Clean up */
        if ($this->acquired) {
            $this->release();
        }

        return sem_remove($this->id);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * returns true if lock is in place
     *
     * @return bool
     */
    public function isAcquired()
    {
        return $this->acquired;
    }
}