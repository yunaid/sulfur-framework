<?php

namespace Sulfur;

use Aura\Router\RouterContainer;

use Sulfur\Contract\Router as Contract;
use Sulfur\Contract\Request;

class Router implements Contract
{

	/**
	 * Default config
	 * @var array
	 */
	protected $config = [
		'path' => '',
		'routes' => [],
	];


	/**
	 * Predefined urls
	 * @var array
	 */
	protected $urls = [];


	/**
	 * Request object
	 * @var \Sulfur\Contract\Request
	 */
	protected $request;


	/**
	 * Router
	 * @var \Aura\Router\RouterContainer
	 */
	protected $router;


	/**
	 * Create a new router
	 * @param \Sulfur\Request $request
	 * @param array $config
	 */
	public function __construct( Request $request, array $config)
	{
		$this->request = $request;
		$this->config = array_merge($this->config, $config);
		$this->router = new RouterContainer();
		$map = $this->router->getMap();

		foreach ($this->config['routes'] as $name => $route) {
			if (isset($route[0]) && isset($route[1])) {

				$pattern = array_shift($route);
				$handler = array_shift($route);

				$options = [
					'methods' => ['GET', 'POST'],
					'defaults' => [],
					'tokens' => [],
					'wildcard' => null,
					'host' => null,
					'accepts' => null,
					'secure' => null,
					'routable' => true,
					'auth' => null,
					'tokens' =>[],
					'extras' => []
				];

				foreach($route as $option => $value){
					if(isset($options[$option])) {
						$options[$option] = $value;
					} else {
						$options['extras'][$option] = $value;
					}
				}

				if (!is_array($options['methods'])) {
					$options['methods'] = [$options['methods']];
				}

				$route = $map->route($name, $this->config['path'] . $pattern, $handler)
				->allows($options['methods']);

				if ($options['defaults']) {
					$route->defaults($options['defaults']);
				}
				if ($options['tokens']) {
					$route->tokens($options['tokens']);
				}
				if ($options['wildcard']) {
					$route->wildcard($options['wildcard']);
				}
				if ($options['host']) {
					$route->host($options['host']);
				}
				if ($options['accepts']) {
					$route->accepts($options['accepts']);
				}
				if ($options['secure']) {
					$route->secure();
				}
				if (!$options['routable']) {
					$route->isRoutable(false);
				}
				if ($options['auth']) {
					$route->auth($options['auth']);
				}
				if ($options['extras']) {
					$route->extras($options['extras']);
				}
			}
		}
	}


	/**
	 * Try to match routes
	 * If no route was matched, returned request has no handler
	 * @return \Sulfur\Request
	 */
	public function run()
	{
		$matcher = $this->router->getMatcher();
		$route = $matcher->match($this->request->raw());
		if ($route) {
			return $this->request
			->attributes(array_merge($route->extras, $route->attributes))
			->handler($route->handler);
		} else {
			return $this->request;
		}
	}


	/**
	 * Add a predefined url
	 * @param string $name
	 * @param string $url
	 */
	public function set($name, $url)
	{
		$this->urls[$name] = $url;
		return $this;
	}


	/**
	 * Create url
	 *
	 * Pass null to get the current url
	 * Pass true to get the current url with querystring
	 * Pass a string that was defined with 'set' to get a predefined url
	 * Pass '/' to get the current url base
	 * Pass routename, [] to build route from params
	 * Pass [] to build from params with default route
	 *
	 * @return string
	 * @throws \Base\RouterException
	 */
	public function url()
	{
		$args = func_get_args();
		if (!isset($args[0])) {
			// return current url
			return $this->_url();
		} elseif ($args[0] === true) {
			// return current url with qs
			$query = $this->request->qs();
			return $this->_url() . ( $query ? ('?' . $query) : '' );
		} elseif (is_string($args[0]) && isset($this->urls[$args[0]])) {
			// get a predefined url
			return $this->urls[$args[0]];
		} elseif ($args[0] === '/') {
			// return current url base
			return $this->_url(['path' => $this->config['path']]) . '/';
		} elseif (is_string($args[0]) && isset($this->config['routes'][$args[0]])) {
			$route = $args[0];
			$params = isset($args[1]) ? $args[1] : [];
			$parts = isset($args[2]) ? $args[2] : [];
		} else {
			$route = null;
			$params = [];
			$parts = [];
		}

		// build path
		$parts['path'] = $this->path($route, $params);

		return $this->_url($parts);
	}


	protected function path($route, $params)
	{
		return $this->router->getGenerator()->generate($route, $params);
	}


	protected function _url(array $parts = [])
	{
		if (isset($parts['base'])) {
			// get base from components
			$base = $parts['base'];
		} else {
			// build base url
			$scheme = isset($parts['scheme']) ? $parts['scheme'] : $this->request->scheme();
			$domain = isset($parts['domain']) ? $parts['domain'] : $this->request->domain();

			if(isset($parts['port'])) {
				$port = (string) $parts['port'];
			} elseif ($scheme === 'https') {
				$port = 443;
			} else {
				$port = $this->request->port();
			}
			$port = $port === 80 && $scheme === 'http' || $port === 443 && $protocol === 'https' ? '' : ':'.$port;
			$base = ($scheme !== '' ? $scheme . '://' : '//') . $domain . $port. '/';
		}
		// get path
		$path = isset($parts['path']) ? $parts['path'] : $this->request->path();
		// query string
		$query = isset($parts['query']) ? '?' . $parts['query'] : '';
		// get fragment
		$fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
		// done
		return  rtrim($base, '/') . '/' . ltrim($path, '/') . $query . $fragment;
	}


}