<?php

namespace Imagine\IoC;

interface ContainerInterface{
	public function register(string $service, $callback = null, array $methods = [], $shared = false);
	public function make(string $service, array $parameters = []);
	public function resolve(string $service, array $parameters = []);
	public function build(string $callback, array $parameters = []);
}