<?php



/**
 *
 * Usage:
 * $container = new Sulfur\Container();
 *
 * ----------------------------------------------------------
 *
 * When resolving foo, The container return the value 'bar'
 *
 * $container->set( ':foo', 'bar' );
 *
 * ----------------------------------------------------------
 *
 * When resolving Foo, The container will resolve Bar instead
 *
 * $container->set( '@Foo', 'Bar' );
 *
 * ----------------------------------------------------------
 *
 * When resolving Foo, the container will use the provided callable
 *
 * $container->set( '~Foo', function(){
 *		return new Bar();
 * });
 *
 * ----------------------------------------------------------
 *
 * When resolving Foo, the container will use the provided callable with an additional arguments
 *
 * $container->set( '~Foo', ['Factory::make', ':var1' => 'value' ]);
 *
 * ----------------------------------------------------------
 *
 * When resolving Foo, the container will use the Foo class with default value of 'value' for $var for the constructor
 *
 * $container->set( 'Foo', [':var' => 'value' ]);
 *
 * ----------------------------------------------------------
 *
 * When resolving Foo, the container will call the callable to provide a default argument for $var
 *
 * $container->set( 'Foo', ['~var' => function(){
 *	return 'value';
 * } ]);
 *
 * ----------------------------------------------------------
 *
 * When resolving Foo, the container will resolve Bar to provide a default argument for $var
 *
 * $container->set( 'Foo', ['@var' => 'Bar' ]);
 *
 * ----------------------------------------------------------
 *
 * When resolving Foo, the container will use typehints to resolve var and provide 'value' for $nestedvar of the reolving constructor
 *
 * $container->set( 'Foo', ['var' => [':nestedvar' => 'value' ] ]);
 *
 * ----------------------------------------------------------
 *
 * Use the container to get an instance
 *
 * $foo = $container->get('Foo');
 *
 * ----------------------------------------------------------
 *
 * Use the container to get an instance and provide arguments
 *
 * $foo = $container->get('Foo', [':val', => 'value']);
 *
 *
 */

namespace Sulfur;

use Sulfur\Contract\Container as Contract;
use Exception;

class Container implements Contract
{

	/**
	 * Number of times a resolving class can depend on the same class
	 */
	const MAX_NESTING = 10;

	/**
	 * Delegates and/or aliases and/or arguments for names
	 * @var array
	 */
	protected $definitions = [];

	/**
	 * Definitions marked as shared
	 * @var array
	 */
	protected $shared = [];

	/**
	 * Created instances
	 * @var array
	 */
	protected $instances = [];

	/**
	 * Definitions that are resolving
	 * @var array
	 */
	protected $resolving = [];


	/**
	 * Add a new definition or definitions
	 * @param array | string $nameOrDefinitions
	 * @param Mixed $definition
	 * @return \Sulfur\Container
	 */
	public function set($nameOrDefinitions, $definition = null)
	{
		// always work with an array
		if($definition !== null){
			// name definition pair given
			$definitions = [$nameOrDefinitions => $definition];
			$names = [$nameOrDefinitions];
			$size = 1;
		} else {
			// assoc array given
			$definitions = $nameOrDefinitions;
			$names = array_keys($definitions);
			$size = count($names);
		}
		// loop throught the array and create an array for each name
		// then store the definition in that array.
		// With more calls to set, the arrays can fill up with more than one definition

		// Use the for loop + array_keys + size for speed
		for ($i=0; $i < $size; $i++) {
			$name = $names[$i];
			$this->definitions[$name] = $definitions[$name];
		}
		return $this;
	}


	/**
	 * Set instance and mark as shared
	 * Or mark single definition as shared
	 * Or mark multiple definitions as shared
	 * @param string|array $nameOrNames
	 * @param mixed $instance
	 * @return \Sulfur\Container
	 */
	public function share($nameOrNames, $instance = null)
	{
		if (is_array($nameOrNames)) {
			// flag them as shared all at once
			$this->shared = array_merge($this->shared, array_fill_keys($nameOrNames, true));
		} else {
			$this->shared[$nameOrNames] = true;
			if($instance !== null){
				$this->instances[$nameOrNames] = $instance;
			}
		}
		return $this;
	}


	/**
	 * Get shared instance or create a new one
	 * @param string $name
	 * @param array $args
	 * @param boolean $make Force the creation of a new instance
	 * @return mixed
	 */
	public function get($name, array $args = [], $make = false)
	{
		// check shared instances first
		if(isset($this->shared[$name]) && isset($this->instances[$name]) && $make === false){
			return $this->instances[$name];
		}

		if(($key = '@' . $name) && key_exists($key, $this->definitions)){
			// Alias definition
			$definition = $this->definitions[$key];
			if(is_string($definition)) {
				// Alias given for this class
				$instance = $this->get($definition);
			} elseif(is_array($definition)) {
				// Alias given with additional arguments
				$instance = $this->get($definition[0], array_merge($definition, $args));
			}
		} elseif(($key = '~' . $name) && key_exists($key, $this->definitions)){
			// Delegate
			$definition = $this->definitions[$key];
			if( is_array($definition) && (! isset($definition[1]) || ! is_string($definition[1]) ) ){
				// It is an array, but not of the type [$object, 'string']
				// A callable should be given at the [0] position. Call it.
				// Arguments may be present as named keys. Just pass in the entire definition.
				// The ::call method will only be interested in named keys, so [0] will be ignored
				$instance = $this->call($definition[0], array_merge($definition, $args));
			} else {
				// Something else: treat it as a callable
				$instance = $this->call($definition, $args);
			}
		} elseif(($key = ':' . $name) && key_exists($key, $this->definitions)){
			// literal value given
			$instance = $this->definitions[$key];
		} elseif(isset($this->definitions[$name]) && is_array($this->definitions[$name])) {
			// Arguments for constructor given
			$instance = $this->instance($name, array_merge( $this->definitions[$name], $args));
		} else {
			// No definition found: try it without any help
			$instance = $this->instance($name);
		}

		$this->instances[$name] = $instance;
		return $instance;
	}


	/**
	 * Call a callable and resolve it's dependencies
	 * @param callable $callable
	 * @param array $args
	 * @param string $context
	 * @return mixed
	 */
	public function call($callable, array $args = [], $context = null)
	{
		// Check if there are default arguments given under the context key
		if($context && isset($this->definitions[$context])) {
			$args = array_merge($this->definitions[$context], $args);
		}

		// Get the right kind of reflector for the callable
		if($callable instanceof \Closure){
			// Callable is a closure
			$reflector = new \ReflectionFunction($callable);
		} elseif(is_object($callable) && method_exists($callable, '__invoke')){
			// callable object
			$reflector = new \ReflectionMethod($callable, '__invoke');
		} elseif(is_array($callable) && count($callable) == 2) {
			// array with object, method
			$reflector = new \ReflectionMethod($callable[0], $callable[1]);
		} elseif(is_string($callable)) {
			$parts = explode('::', $callable);
			if(count($parts) == 2){
				// Class::staticmethod
				$reflector = new \ReflectionMethod($parts[0], $parts[1]);
			} else {
				// Function name
				$reflector = new \ReflectionFunction($callable);
			}
		} else {
			throw new Exception('Invalid callable given');
		}

		// Fetch arguments, call and return result
		return call_user_func_array($callable, $this->arguments($reflector, $args));
	}


	/**
	 * Create an instance with the provided data
	 * @param string $class
	 * @param array $args
	 * @return mixed
	 * @throws Exception
	 */
	protected function instance($class, array $args = [])
	{
		// Prevent recursive dependencies
		if(isset($this->resolving[$class]) && count($this->resolving[$class]) > self::MAX_NESTING){
			throw new Exception();
		}
		if(! isset($this->resolving[$class])) {
			$this->resolving[$class] = [];
		}
		$this->resolving[$class][] = 1;

		// Provide dependencies for constructor & instantiate
		$reflector = new \ReflectionClass($class);
		$constructor = $reflector->getConstructor();
		if(! $constructor){
			$instance = $reflector->newInstance();
		} else {
			$instance = $reflector->newInstanceArgs( $this->arguments($constructor, $args) );
		}

		// Done resolving
		array_pop($this->resolving[$class]);

		return $instance;
	}


	/**
	 * Resolve the params and provide arguments for them
	 * @param \Reflector $reflector
	 * @param array $args
	 * @return array
	 * @throws \Exception
	 */
	protected function arguments(\Reflector $reflector, $args)
	{

		$params = $reflector->getParameters();
		$arguments = [];

		foreach($params as $param){
			$name = $param->getName();
			if(($key = ':' . $name) && key_exists($key, $args)){
				// A literal value was given
				$arguments[] = $args[$key];
			} elseif(($key = '~' . $name) && key_exists($key, $args)){
				// A delegate was given
				$definition = $args[$key];
				if( is_array($definition) && (! isset($definition[1]) || ! is_string($definition[1]) ) ){
					// An array, but not callable
					// Callable with additional arguments given
					$arguments[] = $this->call($definition[0], $definition);
				} else {
					// something else: treat it as a callable
					$arguments[] = $this->call($definition);
				}
			}  elseif(($key = '@'. $name) && key_exists($key, $args)){
				// An alias was given
				$definition = $args[$key];
				if(is_array($definition)){
					// Alias with arguments given
					$arguments[] = $this->get($definition[0], $definition);
				} else {
					// Only an alias given
					$arguments[] = $this->get($definition);
				}
			} else {
				// No specific argument-help provided, try to resolve it though typehints
				$argument = null;
				$exception = null;

				if($class = $param->getClass()){
					// Typehinted class parameter, try to resolve it through the container.
					try{
						if(isset($args[$name]) && is_array($args[$name])) {
							// there are default arguments provided for provisioning of the typehinted parameter
							$argument = $this->get($class->getName(), $args[$name]);
						} else {
							// try to get the object with no arguments
							$argument = $this->get($class->getName());
						}
					} catch(Exception $e) {
						// The exception is thrown by this method, but for a deeper dependency
						// Catch it for now. We want to try a default value first
						// if that fails too, we'll throw this deep exception instead of a new one
						// It helps pinpointing the source of the error when debugging
						$exception = $e;
					}
				}

				if($argument) {
					// We found a typehinted argument
					$arguments[] = $argument;
				} elseif($param->isDefaultValueAvailable()) {
					// There is a default value, provide it.
					$arguments[] = $param->getDefaultValue();
				} elseif($param->isOptional()) {
					// PDO class has optional params without defaultvalue. provide null.
					$arguments[] = null;
				} else {
					// Can't do it
					if($exception) {
						// if a deeper exception was caught when resolving via typehinted argument. rethrow it here
						// so it will bubble up.
						throw $exception;
					} else {
						// if not: throw a new exception
						if($reflector->getName() === '__construct' ) {
							$message = 'Could not provision parameter $' . $name . ' for class ' . $reflector->getDeclaringClass()->getName() . ' in ' .  $reflector->getFileName();
						} else {
							$message = 'Could not provision parameter $' . $name . ' for ' . $reflector->getName() . ' in ' .  $reflector->getFileName() . ':' . $reflector->getStartLine();
						}
						throw new Exception( $message );
					}
				}
			}
		}
		return $arguments;
	}
}