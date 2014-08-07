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
class Service implements \ArrayAccess, \Countable
{
    /** @var array */
    protected $container = array();
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
     * @param array $data
     */
    function __construct(array $data = array())
    {
        $this->services   = new \SplObjectStorage();
        $this->parameters = new \SplObjectStorage();
    }

    /**
     * service and parameters passed as argument here
     * to register a stack of parameters/services @ once
     *
     * @param array $data
     */
    public function addArray(array $data)
    {
        foreach ($data as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * get all names of registered
     * services and parameters
     *
     * @return array
     */
    public function getNames()
    {
        return array_keys($this->container);
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
     * @return array|int
     */
    public function count()
    {
        return count($this->container);
    }

    /**
     * magic method so factory class can call on the protected properties
     *
     * @param   $id
     * @return  null
     */
    public function __get($id)
    {
        if (property_exists($this, $id) && next(debug_backtrace())['class'] == __NAMESPACE__ . '\Factory') {
            return $this->$id;
        } else {
            $trace = debug_backtrace();
            trigger_error(sprintf('Undefined property: %s::$%s in %s(%s)', $trace[0]['class'], $id, $trace[0]['file'], $trace[0]['line']), E_USER_NOTICE);
            return null;
        }
    }

    /**
     * magic method so factory class can set the protected properties
     *
     * @param $id
     * @param $value
     */
    public function __set($id, $value)
    {
        if (property_exists($this, $id) && next(debug_backtrace())['class'] == __NAMESPACE__ . '\Factory') {
            $this->$name = $value;
        }
    }

    /**
     * Get factory
     *
     * @return Factory
     */
    public function getFactory()
    {
        return new Factory($this);
    }



}
