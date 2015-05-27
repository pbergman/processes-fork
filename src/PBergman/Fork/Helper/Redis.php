<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\Fork\Helper;

use \Redis as BaseRedis;

/**
 * Class Redis
 *
 * @package PBergman\Fork\Helper
 */
class Redis extends BaseRedis
{
    protected $options = [];

    /**
     * same as reconnect but will not close connection
     */
    function __clone()
    {
        $this->connect($this->getHost(), $this->getPort(), $this->GetTimeout());
        foreach ($this->options as $n => $v) {
            $this->setOption($n, $v);
        }
    }

    /**
     * duplicate connection and return the instance
     *
     * @return Redis
     */
    public function duplicate()
    {
        $dup = new self();
        $dup->connect($this->getHost(), $this->getPort(), $this->GetTimeout());
        foreach ($this->options as $n => $v) {
            $dup->setOption($n, $v);
        }
        return $dup;
    }

    /**
     * @inheritdoc
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return parent::setOption($name, $value);
    }

    /**
     * will close current connection, start a new connection and set reset the options
     *
     * @throws \RedisException
     * @return bool|self
     */
    public function reconnect()
    {
        if ($this->IsConnected()) {
            list($host, $port, $timeout) = [$this->getHost(), $this->getPort(), $this->GetTimeout()];
            $this->close();
            $ret = $this->connect($host, $port, $timeout);
            foreach ($this->options as $n => $v) {
                $this->setOption($n, $v);
            }
            return $ret;
        } else {
            throw new \RedisException('Need to be connected!');
        }
    }


    /**
     * set sa lock on given key with ttl when the lock needs to expire
     * this is compatible with redis < 2.6.12, new version can use
     * $this->set($key, $token, ['nx', 'ex' => $ttl])
     * but older version don`t support and need lua script
     *
     * @param string $key
     * @param int $ttl
     *
     * @return array|bool
     * @throws \RedisException
     */
    public function lock($key, $ttl, $blocking = true)
    {
        $token = strtoupper(uniqid('LOCK'));
        $script = <<<EOS
        local lock = redis.call('setnx', KEYS[1], KEYS[3])
        if lock == 1 then
          redis.call('pexpire', KEYS[1], KEYS[2])
        end
        return lock
EOS;
        if (false !== $hash = $this->script('load', $script)) {
            $ret = (bool) $this->evalSha($hash, array($key, $ttl, $token), 3);
            while ($blocking && false === $ret) {
                $ret = (bool) $this->evalSha($hash, array($key, $ttl, $token), 3);
            }
        } else {
            throw new \RedisException('Could not load lock script');
        }

        return ($ret) ? [$key, $token] : $ret;
    }

    /**
     * @param   array $lock
     * @throws  \RedisException
     */
    public function release(array $lock)
    {
        if (count($lock) !== 2) {
            throw new \RedisException('Invalid lock result given, expecting: [key, token]');
        }
        list($key, $token) = $lock;
        $script = <<<EOS
        if redis.call("GET", KEYS[1]) == ARGV[1] then
            return redis.call("DEL", KEYS[1])
        else
            return 0
        end
EOS;
        if (false !== $hash = $this->script('load', $script)) {
            return (bool) $this->evalSha($hash, array($key, $token), 1);
        } else {
            throw new \RedisException('Could not load release script');
        }
    }
}