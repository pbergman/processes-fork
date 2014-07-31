<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Container;

/**
 * Class Container
 *
 * @package PBergman\Container
 */
class Service implements \ArrayAccess
{
    const STATIC_SERVICE  = 1;
    const PARAMETER       = 2;

    /** @var array */
    protected $container;
    /** @var array */
    protected $raw;
    /** @var \SplObjectStorage  */
    protected $services;
    /** @var \SplObjectStorage  */
    protected $parameters;
    /** @var array */
    protected $locked;

    /**
     * construct of container
     *
     * service and parameters can be passed as argument here
     *
     * @param array $data
     */
    function __construct(array $data = array())
    {
        $this->services   = new \SplObjectStorage();
        $this->parameters = new \SplObjectStorage();

        foreach ($data as $key => $value) {
            $this->offsetSet($key, $value);
        }

    }

    /**
     * Check if offset (key) exists in container
     *
     * @param   mixed   $id
     *
     * @return  bool
     */
    public function offsetExists($id)
    {
        return isset($this->container[$id]);
    }

    /**
     * register service/parameter by array access, so for example:
     *
     *  $c = new Container();
     *  $c['foo']    = 'SOME_PARAMETER_VALUE';
     *  $c['foofoo'] = function(Container $c) { return $c['foo']; };
     *
     *  echo $c['foofoo']; // Will output "SOME_PARAMETER_VALUE"
     *
     * @param   mixed $id
     * @param   mixed $value
     *
     * @throws  \RuntimeException
     */
    public function offsetSet($id, $value)
    {
        if (isset($this->locked[$id])) {
            throw new \RuntimeException(sprintf('Service is locked "%s".', $id));
        }

        $this->container[$id] = $value;
    }

    /**
     * un register a service
     *
     * @param mixed $id
     */
    public function offsetUnset($id)
    {
        if (isset($this->container[$id])) {

            if (is_object($this->container[$id])) {

                if ($this->services->contains($this->container[$id])) {
                    $this->services->detach($this->container[$id]);
                }

                if ($this->parameters->contains($this->container[$id])) {
                    $this->parameters->detach($this->container[$id]);
                }
            }

            unset($this->container[$id], $this->locked[$id]);
        }
    }

    /**
     *  get container/parameter, if it called is not a part of parameters,
     *  services or defined in raw and it is callable it will spawn a new
     *  instance and saves it as the service.
     *
     *  see register, to register callable parameters or service that
     *  need a new instance when called.
     *
     * @param   mixed $id
     *
     * @return  mixed|null
     *
     * @throws \InvalidArgumentException
     */
    public function offsetGet($id)
    {
        $return  = null;

        if (!isset($this->container[$id])) {
            throw new \InvalidArgumentException(sprintf('No service or parameter defined with: "%s".', $id));
        }

        if (isset($this->container[$id])) {

            // Check if it is a parameter or all ready processed
            if (isset($this->raw[$id]) || !is_callable($this->container[$id]) || isset($this->parameters[$this->container[$id]])) {
                return $this->container[$id];
            }

            // Check for "static" services
            if (isset($this->services[$this->container[$id]])) {
                return $this->container[$id]($this);
            }

            $raw = $this->container[$id];

            $this->raw[$id]       = $raw;
            $this->container[$id] = $raw($this);
            $this->locked[$id]    = true;

            $return = $this->container[$id];
        }

        return $return;
    }

    /**
     * when register a callable new service/parameter it will see it as a
     * service and will get the same instance as once called. If you want
     * a new instance on calling the service every time you have to register
     * the service like :
     *
     *  $c = new Container();
     *  $c['foo'] = $c->register(function{
     *    return new stdClass();
     *  }, Container::STATIC_SERVICE);
     *
     *  so every time you call $c['foo'] a new instance will be returned
     *
     *  When you need to register a callable parameter you can register it like:
     *
     *  $c = new Container();
     *  $c['foo'] = $c->register(function{
     *    return rand(1,100);
     *  }, Container::PARAMETER);
     *
     *  echo $c['foo']() // Will output random between 1 and 100
     *
     * @param   callable    $data
     * @param   int         $state
     *
     * @return  callable
     */
    public function register(callable $data, $state = self::PARAMETER)
    {
        switch ($state) {
            case self::PARAMETER:
                $this->parameters->attach($data);
                break;
            case self::STATIC_SERVICE:
                $this->services->attach($data);
                break;

        }

        return $data;
    }
}
