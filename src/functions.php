<?php

/**
 * Get the shared container instance
 * @staticvar Sulfur\Contract\Container $container
 * @return Sulfur\Contract\Container
 */
function container($class = null, $args = [])
{
	static $container;
	if(is_null($container)) {
		$container = \Sulfur\App::shared('container');
	}
	if($class !== null) {
		return $container->get($class, $args);
	} else {
		return $container;
	}
}

/**
 * Get a value fron the env.php config file
 * @param string $path
 * @param mixed $default
 * @return mixed
 */
function env($path, $default = null)
{
	return config('env', $path, $default);
}


/**
 * Get a config value or entire resource as array
 * @staticvar Sulfur\Contract\Config $config
 * @param string $resource
 * @param path $path
 * @param mixed $default
 * @return mixed
 */
function config($resource, $path = null, $default = null)
{
	static $config;
	if(is_null($config)) {
		$config = \Sulfur\App::shared('config');
	}
	return $config($resource, $path, $default);
}