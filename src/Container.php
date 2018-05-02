<?php

namespace Sulfur;

use Exception;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionClass;
use Closure;

class Container
{

	/**
	 * Resolver types
	 */
	const TYPE_CONSTRUCT = 'construct';
	const TYPE_RESOLVE = 'resolve';
	const TYPE_METHOD = 'method';
	const TYPE_FUNCTION = 'function';

	/**
	 * Number of times a resolving class can depend on the same class
	 */
	const MAX_NESTING = 5;

	/**
	 * Delegates and/or aliases and/or arguments for names
	 * @var array
	 */
	protected $resolvers = [];

	/**
	 * resolvers marked as shared
	 * @var array
	 */
	protected $shared = [];

	/**
	 * Created instances
	 * @var array
	 */
	protected $instances = [];

	/**
	 * Resolvers that are resolving
	 * @var array
	 */
	protected $resolving = [];

	/**
	 * Cached reflectors
	 * @var array
	 */
	protected $reflected = [];

	/**
	 * Whether the cached reflectors have changed
	 * @var type
	 */
	protected $changed = false;

	/**
	 * Get or set cached data
	 * @param array $cache
	 * @return mixed
	 */
	public function reflected($reflected = null)
	{
		if ($reflected === null) {
			return $this->reflected;
		} else {
			$this->reflected = $reflected;
		}
	}


	/**
	 * Check whether extra methods were reflected (and should be cached)
	 * @return type
	 */
	public function changed()
	{
		return $this->changed;
	}


	/**
	 * Add a new provider or resolvers
	 * @param array | string $nameOrResolvers
	 * @param Mixed $provider
	 * @return Sulfur\Container
	 */
	public function set($nameOrResolvers, $provider = null)
	{
		// always work with an array
		if ($provider !== null) {
			// name provider pair given
			$resolvers = [$nameOrResolvers => $provider];
			$names = [$nameOrResolvers];
			$size = 1;
		} elseif (is_array($nameOrResolvers)) {
			// assoc array given
			$resolvers = $nameOrResolvers;
			$names = array_keys($resolvers);
			$size = count($names);
		} else {
			// only a name given
			$resolvers = [$nameOrResolvers => []];
			$names = [$nameOrResolvers];
			$size = 1;
		}
		// loop throught the array and create an array for each name
		// then store the resolver in that array.
		// With more calls to set, the arrays can fill up with more than one resolver
		// Use the for loop + array_keys + size for speed
		for ($i = 0; $i < $size; $i++) {
			$name = $names[$i];
			$this->resolvers[$name] = $resolvers[$name];
		}
		return $this;
	}


	/**
	 * Set instance and mark as shared
	 * Or mark single resolver as shared
	 * Or mark multiple resolvers as shared
	 * @param string|array $nameOrNames
	 * @param mixed $instance
	 * @return Sulfur\Container
	 */
	public function share($nameOrNames, $instance = null)
	{
		if (is_array($nameOrNames)) {
			// flag them as shared all at once
			$this->shared = array_merge($this->shared, array_fill_keys($nameOrNames, true));
		} else {
			$this->shared[$nameOrNames] = true;
			if ($instance !== null) {
				$this->instances[$nameOrNames] = $instance;
			}
		}
		return $this;
	}


	/**
	 * Force a new instance
	 * @param string $classs
	 * @param array $args
	 * @return mixed
	 */
	public function make($class, array $args = [])
	{
		return $this->resolve($class, $args, true);
	}


	/**
	 * Get an instance
	 * @param string $class
	 * @param array $args
	 * @return mixed
	 */
	public function get($class, array $args = [])
	{
		return $this->resolve($class, $args, false);
	}


	/**
	 * Call somthing
	 * @param callable $callable
	 * @param array $args
	 * @param string $context
	 * @return mixed
	 */
	public function call($callable, array $args = [], $context = null)
	{
		if ($context !== null && key_exists($context, $this->resolvers) && is_array($this->resolvers[$context])) {
			$resolver = $this->resolvers[$context];
			array_unshift($resolver, $callable);
			return $this->resolve($resolver, $args, true);
		} else {
			return $this->resolve($callable, $args, true);
		}
	}


	/**
	 * Resolve a dependency
	 * @param mixed $resolver
	 * @param array $args
	 * @param boolean $make
	 * @return mixed
	 * @throws Exception
	 */
	protected function resolve($resolver, array $args = [], $make = false)
	{

		/* --------------------------------------------------------------
		 * Check what kind of resolver we have, with default args or not.
		 * ------------------------------------------------------------- */
		if (is_array($resolver) && key_exists(0, $resolver) && !key_exists(1, $resolver)) {
			// the first element is the actual resolver, the rest is default arguments
			$default = $resolver;
			$resolver = array_shift($default);
			// merge arguments
			$args = $this->merge($default, $args);
		}


		/* --------------------------------------------------------------
		 * Create a provider: a normalized resolver
		 * ------------------------------------------------------------- */
		// Start with no provider
		$provider = null;

		// Inspect the resolver
		if (is_string($resolver)) {
			if (key_exists($resolver, $this->resolvers)) {
				$mixed = $this->resolvers[$resolver];
				if (is_array($mixed) && !key_exists(0, $mixed)) {
					// Registered resolver only contains default arguments.
					// merge arguments
					$args = $this->merge($mixed, $args);
					// Resolve as a construct
					$provider = [
						'type' => self::TYPE_CONSTRUCT,
						'name' => $resolver,
						'cache' => $resolver . '.__construct',
						'resolver' => $resolver,
					];
				} else {
					// Registered resolver contains actual resolver
					$provider = [
						'type' => self::TYPE_RESOLVE,
						'name' => $resolver,
						'cache' => false,
						'resolver' => $mixed,
					];
				}
			} elseif (strpos($resolver, '::') > 0) {
				// 'Class::method'
				$parts = explode('::', $resolver);
				$provider = [
					'type' => self::TYPE_METHOD,
					'cache' => $parts[0] . '.' . $parts[1],
					'resolver' => $parts,
				];
			} elseif ($resolver[0] === '~') {
				// '~functionname'
				$fn = substr($resolver, 1);
				$provider = [
					'type' => self::TYPE_FUNCTION,
					'cache' => $fn,
					'resolver' => $fn,
				];
			} else {
				// 'Classname'
				$provider = [
					'type' => self::TYPE_CONSTRUCT,
					'name' => $resolver,
					'cache' => $resolver . '.__construct',
					'resolver' => $resolver,
				];
			}
		} elseif ($resolver instanceof Closure) {
			// function(){}
			$provider = [
				'type' => self::TYPE_FUNCTION,
				'cache' => false,
				'resolver' => $resolver,
			];
		} elseif (is_object($resolver) && method_exists($resolver, '__invoke')) {
			$provider = [
				'type' => self::TYPE_METHOD,
				'cache' => get_class($resolver) . '.__invoke',
				'resolver' => [$resolver, '__invoke'],
			];
		} elseif (is_array($resolver) && isset($resolver[0]) && isset($resolver[1])) {
			// array with object or class, methodname
			if (is_object($resolver[0]) && is_string($resolver[1])) {
				$provider = [
					'type' => self::TYPE_METHOD,
					'cache' => get_class($resolver[0]) . '.' . $resolver[1],
					'resolver' => [$resolver[0], $resolver[1]],
				];
			} elseif (is_string($resolver[0]) && is_string($resolver[1])) {
				$provider = [
					'type' => self::TYPE_METHOD,
					'cache' => $resolver[0] . '.' . $resolver[1],
					'resolver' => [$resolver[0], $resolver[1]],
				];
			}
		}

		// No workable provider could be created from resolver
		if (!$provider) {
			throw new Exception('Invalid resolver');
		}



		/* --------------------------------------------------------------
		 * When the provider has a name, we should do a few things here.
		 * Check circular dependencies
		 * Check shared instances
		 * ------------------------------------------------------------- */
		if (isset($provider['name'])) {
			// Check if we should use a shared instance
			if ($make === false && key_exists($provider['name'], $this->shared) && key_exists($provider['name'], $this->instances)) {
				return $this->instances[$provider['name']];
			}

			// Prevent circular dependencies
			if (isset($this->resolving[$provider['name']]) && $this->resolving[$provider['name']] > self::MAX_NESTING) {
				throw new Exception('Max nested dependencies exceeded');
			}
			if (!isset($this->resolving[$provider['name']])) {
				$this->resolving[$provider['name']] = 0;
			}
			$this->resolving[$provider['name']] ++;
		}



		/* --------------------------------------------------------------
		 * Use the provider to resolve
		 * ------------------------------------------------------------- */
		if ($provider['type'] === self::TYPE_RESOLVE) {

			/* --------------------------------------------------------------
			 * There was a registered resolver
			 * Resolve through container
			 * ------------------------------------------------------------- */
			$instance = $this->resolve($provider['resolver'], $args, false);
		} else {

			/* --------------------------------------------------------------
			 * All other types: resolve here
			 * Get parameter info of resolver
			 * ------------------------------------------------------------- */
			if ($provider['cache'] && isset($this->reflected[$provider['cache']])) {
				// Reflection info exists in cache
				$params = $this->reflected[$provider['cache']];
			} else {
				// Get reflection from resolver
				$reflector = null;
				switch ($provider['type']) {
					case self::TYPE_METHOD:
						$reflector = new ReflectionMethod($provider['resolver'][0], $provider['resolver'][1]);
						break;
					case self::TYPE_CONSTRUCT:
						if (method_exists($provider['resolver'], '__construct')) {
							$reflector = new ReflectionMethod($provider['resolver'], '__construct');
						}
						break;
					case self::TYPE_FUNCTION:
						$reflector = new ReflectionFunction($provider['resolver']);
						break;
				}
				// Get parameter info from reflection
				$params = [];
				if ($reflector) {
					foreach ($reflector->getParameters() as $param) {
						$hasDefault = $param->isDefaultValueAvailable();
						$params[$param->getName()] = [
							'hasDefault' => $hasDefault,
							'default' => $hasDefault ? $param->getDefaultValue() : null,
							'optional' => $param->isOptional(),
							'class' => ($class = $param->getClass()) ? $class->getName() : null
						];
					}
				}
				// Cache it if there was a cache key
				if ($provider['cache']) {
					// Flag as changed
					$this->changed = true;
					$this->reflected[$provider['cache']] = $params;
				}
			}


			/* --------------------------------------------------------------
			 * Build arguments for the resolver from the found params
			 * ------------------------------------------------------------- */
			$arguments = [];
			foreach ($params as $name => $param) {
				// Literal value in the args
				if (($literal = ':' . $name) && key_exists($literal, $args)) {
					// Literal argument in the arguments provided
					$arguments[] = $args[$literal];
					continue;
				}

				// Helper given in the args, can be a new resolver or an array of default arguments
				if (key_exists($name, $args)) {
					$mixed = $args[$name];
					if (is_array($mixed) && !isset($mixed[0])) {
						// Only default arguments given in the args
						// continue to resolve though typehints with these argumentArgs values
						$argumentArgs = $mixed;
					} else {
						// Complete resolver given in the args: resolve it
						$arguments[] = $this->resolve($mixed, [], false);
						continue;
					}
				} else {
					$argumentArgs = [];
				}

				// No specific argument-help provided, try to resolve it through typehints
				$argument = null;
				$exception = null;
				if ($param['class']) {
					try {
						$argument = $this->resolve($param['class'], $argumentArgs, false);
					} catch (Exception $e) {
						// The exception is thrown by this method, but for a deeper dependency
						// Catch it for now. We want to try a default value first
						// if that fails too, we'll throw this deep exception instead of a new one
						// It helps pinpointing the source of the error when debugging
						$exception = $e;
					}
				}

				if ($argument) {
					// We found a typehinted argument
					$arguments[] = $argument;
				} elseif ($param['hasDefault']) {
					// There is a default value, provide it.
					$arguments[] = $param['default'];
				} elseif ($param['optional']) {
					// PDO class has optional params without defaultvalue. provide null.
					$arguments[] = null;
				} else {
					// Can't do it
					if ($exception) {
						// if a deeper exception was caught when resolving via typehinted argument. rethrow it here
						// so it will bubble up.
						throw $exception;
					} else {
						// if not: throw a new exception
						if ($provider['type'] === self::TYPE_CONSTRUCT) {
							$message = 'Could not provision parameter $' . $name . ' for class ' . $provider['resolver'];
						} else {
							$message = 'Could not provision parameter $' . $name . ' for delegate';
						}
						throw new Exception($message);
					}
				}
			}

			/* --------------------------------------------------------------
			 * Use the artguments to create instance
			 * ------------------------------------------------------------- */
			if ($provider['type'] === self::TYPE_CONSTRUCT) {
				// Resolve by creating new class
				$instance = (new ReflectionClass($provider['resolver']))->newInstanceArgs($arguments);
				//$instance = new $provider['resolver'](...$arguments);
			} else {
				// Resolve through calling callable
				$instance = call_user_func_array($provider['resolver'], $arguments);
			}
		}


		/* --------------------------------------------------------------
		 * When there is a name, Do a few extra things
		 * Keep track of circular dependencies
		 * Share if needed
		 * ------------------------------------------------------------- */
		if (isset($provider['name'])) {
			// Circular dependency tracker
			$this->resolving[$provider['name']] --;
			// Shared instances
			$this->instances[$provider['name']] = $instance;
		}

		// Done!
		return $instance;
	}


	/**
	 * Merge arguments and default arguments
	 * @param array $default
	 * @param array $args
	 * @return array
	 */
	protected function merge(array $default, array $args)
	{
		if (count($default) == 0) {
			return $args;
		}

		if (count($args) == 0) {
			return $default;
		}

		$merged = $default;
		foreach ($args as $key => $value) {
			if ($key[0] === ':') {
				unset($merged[substr($key, 1)]);
			} else {
				unset($merged[':' . $key]);
			}
			$merged[$key] = $value;
		}
		return $merged;
	}


}
