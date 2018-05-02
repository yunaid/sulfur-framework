<?php

/**
 * Main Sulfur class to handle a request in a compact way
 */

namespace Sulfur;

use Sulfur\Config;
use Sulfur\Container;
use Sulfur\Framework\Cache;
use Sulfur\Router;
use Sulfur\Request;
use Sulfur\Response;


class App
{

	/**
	 * Handle a http request
	 * @param array $paths path that need to be checked for config resources
	 * @param array $env env variables
	 * @param string $config optional config class
	 */
	public static function http($paths, $env, $config = null)
	{
		$app = new self($paths, $env, $config);
		$app->handleHttp();
	}


	/**
	 * Handle a console request
	 * @param array $paths path that need to be checked for config resources
	 * @param array $env env variables
	 * @param string $config optional config class
	 */
	public static function console($paths, $env, $config = null)
	{
		$app = new self($paths, $env, $config);
		$app->handleConsole();
	}


	/**
	 * Container instance
	 * @var Sulfur\Container
	 */
	protected $container;

	/**
	 * Config instance
	 * @var Sulfur\Config
	 */
	protected $config;

	/**
	 * Cache key constructed from the config paths
	 * @var string
	 */
	protected $key;

	/**
	 * Framework cache
	 * @var Sulfur\Framework\Cache
	 */
	protected $cache;


	/**
	 * Stack of middelware
	 * @var array
	 */
	protected $middleware = [];


	/**
	 * App constructor
	 * Create container and config
	 * Share container and config in container
	 * Load config and container from cache
	 * PHP settings
	 * Providers
	 *
	 * @param array $paths path that need to be checked for config resources
	 * @param array $env env variables
	 * @param string $config optional config class
	 */
	public function __construct($paths, $env, $config = null)
	{
		// create an application key based on the config paths
		$this->key = md5(implode(',', $paths));

		// create a container
        $this->container = new Container();
		// share the container in itself
		$this->container->share(Container::class, $this->container);

		// create config object
		$class = $config ?: Config::class;
		$this->config = new $class($paths, $env);
		// set the env as resource
		$this->config->resources(['env' => $env]);
		 // share the config in the container
		 $this->container->share(Config::class, $this->config);

		// internal caching
		$config = $this->config->framework('cache');
		if(isset($config['active']) && $config['active']) {
			$class = isset($config['class']) ? $config['class'] : Cache::class;
			$this->cache = new $class($config);
			$this->config->resources($this->cache->data('config_' . $this->key));
			$this->container->reflected($this->cache->data('container_' . $this->key));
		}

		// PHP settings
		$php = $this->config->php();
		date_default_timezone_set($php['timezone']);
		setlocale(LC_ALL, $php['locale']);
		ini_set('display_errors', $php['display_errors']);
		error_reporting($php['error_reporting']);

		// Register providers
		$providers = $this->config->container();
		foreach($providers as $provider) {
			call_user_func([$provider, 'register'], $this->container);
		}
    }


	/**
	 * Get the used container object
	 * @return Sulfur\Container
	 */
	public function container()
	{
		return $this->container;
	}


	/**
	 * Get the used config object
	 * @return Sulfur\Config
	 */
	public function config()
	{
		return $this->config;
	}


	/**
	 * Handle a http request
	 * Get a request object from the router and run it through the handlers
	 */
	public function handleHttp()
	{
		// create the router
		$router = $this->container->get(Router::class);

		// get prerendered routesmap
		if($this->cache && $map = $this->cache->data('router_' . $this->key)) {
			$router->map($map);
		}

		// get request
		$request = $this->container->get(Request::class);

		// get request attributes from the router
		if($attributes = $router->match($request->path(false), $request->method(), $request->domain())) {
			$request->attributes($attributes);
		}

		// create an empty response object
		$response = $this->container->get(Response::class);

		// set middleware handlers
		$this->middleware = $this->config->__invoke('middleware');

		// Run it through the handlers
		$response = $this->handle($request, $response);

		// Send response
		$response->send();

		// cache config data
		if($this->cache && $this->config->changed()) {
			$this->cache->data('config_' . $this->key, $this->config->resources());
		}

		// cache container data
		if($this->cache && $this->container->changed()) {
			$this->cache->data('container_' . $this->key, $this->container->reflected());
		}

		// cache router data
		if($this->cache) {
			$this->cache->data('router_' . $this->key, $router->map());
		}
	}



	/**
	 * Run a request through the stack of middleware
	 * @staticvar int $i
	 * @param Sulfur\Request $request
	 * @param Sulfur\Response $response
	 * @return Sulfur\Response
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