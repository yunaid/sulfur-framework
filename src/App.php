<?php

/**
 * Main Sulfur class to handle a request in a compact way
 * Also serves as a registry to share the container and config globally
 */

namespace Sulfur;

use \Sulfur\Config;
use \Sulfur\Container;
use \Sulfur\Router;
use \Sulfur\Request;
use \Sulfur\Response;


class App
{
	/**
	 * Globally shared variables
	 * @var array
	 */
	protected static $shared = [];


	/**
	 * Share a variable globally
	 * @param string $name
	 * @param mixed $value
	 */
	public static function share($name, $value)
	{
		self::$shared[$name] = $value;
	}


	/**
	 * Get a globally shared variable
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function shared($name, $default = null)
	{
		if(isset(self::$shared[$name])) {
			return self::$shared[$name];
		} else {
			return $default;
		}
	}


	/**
	 * Handle a http request
	 * @param \Sulfur\Contract\Config $config
	 */
	public static function http(Config $config)
	{
		$app = new self($config);
		$app->handleHttp();
	}


	/**
	 * Handle a console request
	 * @param \Sulfur\Contract\Config $config
	 */
	public static function console(Config $config)
	{
		$app = new self($config);
		$app->handleConsole();
	}


	/**
	 * Container instance
	 * @var \Sulfur\Contract\Container
	 */
	protected $container;

	/**
	 * Config instance
	 * @var \Sulfur\Contract\Config
	 */
	protected $config;

	/**
	 * Stack of middelware
	 * @var array
	 */
	protected $middleware = [];


	/**
	 * App constructor
	 * Globalize config and container
	 * PHP settings
	 * @param \Sulfur\Contract\Config $config
	 */
	public function __construct(Config $config)
	{
		// create a container
        $this->container = new Container();
		// share the container globally for helper functions
		self::share('container', $this->container);
		// share the container in itself
		 $this->container->share(Container::class, $this->container);


		// store config locally
		$this->config = $config;
		// share the config globally for helper functions
		self::share('config', $config);
		 // share the config in the container
		 $this->container->share(Config::class, $config);

		// PHP settings
		$php = $config('php');
		date_default_timezone_set($php['timezone']);
		setlocale(LC_ALL, $php['locale']);
		ini_set('display_errors', $php['display_errors']);
		error_reporting($php['error_reporting']);

		// register providers
		$providers = $config->container();
		foreach($providers as $provider) {
			call_user_func([$provider, 'register'], $this->container);
		}
    }


	/**
	 * Handle a http request
	 * Get a request object from the router and run it through the handlers
	 */
	public function handleHttp()
	{
		// create the router
		$router = $this->container->get(Router::class);
		// get a request from the router
		$request = $router->run();
		// create an empty response object
		$response = $this->container->get(Response::class);
		// set middleware handlers
		$this->middleware = $this->config->__invoke('middleware');
		// Run it through the handlers
		$response = $this->handle($request, $response);
		// Send response
		$response->send();
	}



	/**
	 * Run a request through the stack of middleware
	 * @staticvar int $i
	 * @param \Sulfur\Request $request
	 * @param \Sulfur\Response $response
	 * @return \Sulfur\Response
	 */
	protected function handle(Request $request, Response $response)
	{
		static $i;
		if(is_null($i)){
			$i = 0;
		}
		if(isset($this->middleware[$i])){
			$handler = $this->container->get($this->middleware[$i]);
			$i++;
			if(isset($this->middleware[$i])){
				$next = function(Request $request, Response $response){
					return $this->handle($request, $response);
				};
			} else {
				$next = function(Request $request, Response $response){
					return $response;
				};
			}
			return $handler($request, $response, $next);
		} else {
			return $response;
		}
	}



	/**
	 * Handle a http request
	 * Get a request object from the router and run it through the handlers
	 */
	public function handleConsole()
	{
		$console = $this->container->get('Sulfur\Console');
	}
}