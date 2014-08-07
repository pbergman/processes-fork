<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Container;

/**
 * Class Factory
 *
 * @package PBergman\Container
 */
class Factory
{
    private $service;

    /**
     * @param Service $service
     */
    function __construct(Service &$service)
    {
        $this->service = $service;
    }

    /**
     * gives the ability to extend a existing registered definition
     *
     * example:
     *
     * $container['foo'] = function(Container $c) {
     *      return new stdClass();
     * };
     *
     * $container->getFactory()->extend('foo', function($foo, Container $c) {
     *      $foo->foo = 'foo';
     *      return $foo;
     * });
     *
     * var_dump($container['foo']);
     *
     * will output:
     *
     * object(stdClass)#10 (1) {
     *      ["foo"]=>
     *      string(3) "foo"
     * }
     *
     * @param   string      $id
     * @param   callable    $callback
     * @return  callable
     *
     * @throws \ErrorException
     * @throws \InvalidArgumentException
     */
    public function extend($id, callable $callback)
    {
        if (!isset($this->service[$id])) {
            throw new \ErrorException(sprintf('Nothing registered by: %s', $id));
        }

        if (!is_callable($this->service->container[$id])) {
            throw new \InvalidArgumentException(sprintf('Extending on parameters (%s) is not allowed', $id));
        }

        $oldObject = $this->service->container[$id];
        $newObject = function() use ($callback, $oldObject) {
            return $callback($oldObject($this->service), $this->service);
        };

        $services = &$this->service->services;

        if ($services->contains($oldObject)) {
            $services->detach($oldObject);
            $services->attach($newObject);
        }

        return $this->container[$id] = $newObject;
    }

    /**
     * will return raw service/parameter definition
     *
     * @param   $id
     * @return  mixed
     * @throws \ErrorException
     */
    public function getRaw($id)
    {
        if (!isset($this->service[$id])) {
            throw new \ErrorException(sprintf('Nothing registered by: %s', $id));
        }

        if (isset($this->service->raw[$id])) {
            return $this->service->raw[$id];
        }

        return $this->service->container[$id];

    }

    /**
     * all closures will be implemented as a service
     * if you want to define a callable parameter you
     * can use this method to set a parameter
     *
     * example:
     *
     *  $container['rand'] = $container->getFactory()->parameter(function(){
     *      return rand(10,50);
     *  });
     *
     *  for ($i = 0; $i < 5; $i++) {
     *      echo $container['rand'](), "\t";
     *  }
     *
     * will output something like :
     *
     * 19   21  26  46  31
     *
     * @param  callable $callback
     * @return callable
     */
    public function parameter(callable $callback)
    {
        $this->service->parameters->attach($callback);
        return $callback;
    }

    /**
     * every time you call a service tou will get the same instance
     * If you want a new instance on every call you can use this method
     *
     * example:
     *
     * $container['rand'] = $container->getFactory()->service(function($c){
     *      $object = new stdClass();
     *      $object->foo = rand(10, 50);
     *      return $object;
     * });
     *
     * for ($i = 0; $i < 2; $i++) {
     *      var_dump($container['rand']);
     * }
     *
     * output:
     *
     * object(stdClass)#5 (1) {
     *      ["foo"]=>
     *      int(36)
     * }
     * object(stdClass)#5 (1) {
     *      ["foo"]=>
     *      int(41)
     * }
     *
     * @param   callable $callback
     * @return  callable
     */
    public function service(callable $callback)
    {
        $this->service->services->attach($callback);
        return $callback;
    }

}