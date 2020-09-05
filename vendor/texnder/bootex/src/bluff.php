<?php

namespace bootex;

use Aditex\src\Container;

abstract class bluff 
{
	/**
	 * accessor must be defined in child class
	 * it should return class name
	 * 
	 * @return 	string|object
	 */
	abstract protected static function getAccessor();

	/**
	 * resolve instance if already created in app
	 * 
	 * @return  object|null
	 */
	public static function getBluffInstance()
	{
		return static::resolveAccessorName(static::getAccessor());
	}

	/**
	 * return resolved instance 
	 *
	 * @return 	null|object
	 */
	protected static function resolveAccessorName($name)
	{
		if (!$name) {
			throw new \RuntimeException('valid accessor name reqiured!');
		}

		if (is_object($name)) {
			return $name;
		}

		if (isset(Container::$storedServices[$name])) {
			return Container::$storedServices[$name];
		}
		
	}


	/**
     * Handle dynamic, static calls to the object.
     *
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::getBluffInstance();

        if (! $instance) {
			$instance = Container::call(static::getAccessor())->get();
        }

        switch (count($args)) {
            case 0:
                return $instance->$method();
            case 1:
                return $instance->$method($args[0]);
            case 2:
                return $instance->$method($args[0], $args[1]);
            case 3:
                return $instance->$method($args[0], $args[1], $args[2]);
            case 4:
                return $instance->$method($args[0], $args[1], $args[2], $args[3]);
            default:
                return call_user_func_array([$instance, $method], $args);
        }
    }
}