<?php

namespace Imagine\IoC;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface{
	/* private $service;
	private $extenders;
	private $shared;
	private $store;
	private $resolved; */
	
	public function __construct(){
		//
	}
	
	
	
	
	
	// Register Services
	public function register(string $service, $callback = null, array $methods = [], $shared = false){
		if(!is_string($service)){
            throw new InvalidArgumentException(sprintf('Parameter "service" must be a string'));
        }elseif(null === $callback){
            $callback = $service;
        }
		
		if(is_callable($callback)){
			$this->addCallback($service, $callback, $shared);
		}elseif(is_array($methods) && !empty($methods)){
			$this->addSetter($service, $callback, $methods, $shared);
		}else{
			$this->addAlias($service, $callback, $shared);
		}
		
		return $this;
	}
	// Add Service Closure
	public function addCallback(string $service, Closure $callback, bool $shared){
        $this->set($service, $callback, $shared);
		
		return $this;
    }
	// Add Service Setter
	public function addSetter(string $service, string $callback, array $methods, bool $shared){
        $define['concrete'] = $callback;
        $define['methods'] = $methods;

        $this->set($service, $define, $shared);
		
		return $this;
    }
	// Add Service Alias
	public function addAlias(string $service, string $callback, bool $shared){
        $this->set($service, $callback, $shared);
		
		return $this;
    }
	
	
	
	
	
	
	public function make(string $service, array $parameters = []){//print_r($service);exit;
		if(isset($this->store[$service]) && !is_null($this->store[$service])){
			return $this->store[$service];
		}
		
		return $this->resolve($service, $parameters);
	}
	public function resolve(string $service, array $parameters = []){
		if($this->has($service) === false){
			//throw new Exception(sprintf('%s is not defined.', $service));
			$this->register($service, $service, [], true);
		}
		
		if(isset($this->bindings[$service])){
			$callback = $this->bindings[$service]['callback'];
		}
		
		$callback = $this->get($service);
		
		if($object = $this->build($callback, $parameters)){
			$this->resolved[$service] = true;
		}
		
		/* foreach($this->getExtenders($service) as $extender){
            $object = $extender($object, $this);
        } */
		
		if($this->isShared($service)){
            $this->store[$service] = $object;
        }
		return $object;
	}
	public function build(string $callback, array $parameters = []){//print_r($callback);exit;
		// if closure
		if($callback instanceof Closure){
            return $callback($this, $parameters);
        }
		
		// Create a reflection.
        $reflector = new ReflectionClass($callback);//print_r($reflector);//exit;
        $name = $reflector->getName();
		$interfaces = $reflector->getInterfaceNames();
		$class = $reflector->getParentClass();
		
		//print_r(array($name, $interfaces, $class));exit;
		// 
		if(!$reflector->isInstantiable()){
            throw new InvalidArgumentException(sprintf('Target[%s] not instantiable', $callback));
        }
		
        // Get it's constructor.
        $constructor = $reflector->getConstructor();//print_r($constructor);exit;
		
		// return if no constructor
        if(is_null($constructor)){
            return new $callback;//print_r($callback);exit;
        }
		
        // Search dependency and resolve. By constructor parameters.
        $args = $this->getDependencies($constructor->getParameters(), $parameters);//print_r($args);exit;

        return $reflector->newInstanceArgs($args);
	}
	public function getDependencies($parameters, $primitives){
		if(empty($parameters)){
			return array();
		}
        $args = [];

        foreach($parameters as $parameter){
            // Get class from param.
            $class = $parameter->getClass();
            
            // Is param a class?
            if($class){
                // Yes, resolve param class, create instance, call resolve.
                $type = $class->name;
                //$type = $parameter->name;

                // Assign to args, resolve.
                $args[] = $this->build($type); // Recursively.
            }elseif($parameter->isDefaultValueAvailable()){
                // Just assign default value.
                $args[] = $parameter->getDefaultValue();
            }else{
                throw new Exception(sprintf('Unresolvable dependency [%s].', $parameter));
            }
        }

        return $args;
    }
	/* public function getExtenders($service){
        if(isset($this->extenders[$service])){
            return $this->extenders[$service];
        }
        return [];
    } */
	protected function isShared($service){
		if(isset($this->shared[$service])){
			return $this->shared[$service];
		}
        return true;
    }
	
//fungsi bawaan
	//set service
	public function set($service, $callback, $shared): void{
		$this->service[$service] = $callback;
		$this->shared[$service] = $shared;
	}
	//get service
	public function get($service){
		if(isset($this->service[$service])){
			return $this->service[$service];
		}
		
		return false;
	}
	//has service
	public function has($service){
		return isset($this->service[$service]);
	}
	public function offsetExists($offset): bool{
        return $this->getContainer()->offsetExists($offset);
    }
	public function offsetGet($offset){
        return $this->getContainer()->offsetGet($offset);
    }
	public function offsetSet($offset, $value): void{
        $this->getContainer()->offsetSet($offset, $value);
    }
	public function offsetUnset($offset): void{
        $this->getContainer()->offsetUnset($offset);
    }
	
}