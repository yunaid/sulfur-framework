<?php

namespace Sulfur;

use Exception;

class Router
{
	/**
	 * Routes
	 * @var array
	 */
	protected $routes = [];

	/**
	 * Separator of tokens
	 * @var string
	 */
	protected $separator;

	/**
	 * Parsed routes
	 * @var array
	 */
	protected $map = null;


	/**
	 * Create a new router
	 * @param array $routes
	 * @param string $separator
	 */
	public function __construct(array $routes = [], $separator = '/')
	{
		$this->routes = $routes;
		$this->separator = $separator;
	}


	/**
	 * Create a map or receive a pre-charted map
	 * @param array $map
	 * @return $this|array
	 */
	public function map($map = null)
	{
		if(is_array($map)) {
			// a cached map is provided, use that
			$this->map = $map;
			return $this;
		}

		if($this->map === null) {

			// init map
			$this->map = [
				'match' => [],
				'build' => [],
				'routes' => [],
			];

			foreach ($this->routes as $name => $route) {
				if (isset($route[0]) && isset($route[1])) {
					// get rules
					$rules = isset($route['rules']) ? $route['rules'] : [];

					// Get tokens from the first element and normalize them
					$tokens = [];
					foreach(explode($this->separator, trim((array_shift($route)), $this->separator)) as $pattern) {

						if($pattern !== '') {
							$token = [
								'pattern' => null,
								'optional' => false,
								'literal' => true,
								'rule' => null,
							];

							// check if it's optional
							if($pattern[0] == '(') {
								$token['optional'] = true;
								$pattern = substr($pattern, 1, -1);
							}

							// check if its literal
							if($pattern[0] == ':') {
								$token['literal'] = false;
								$pattern = substr($pattern, 1);
							}

							// add parsed token
							$token['pattern'] = $pattern;


							if(! $token['literal'] && isset($rules[$pattern])) {
								$token['rule'] = $rules[$pattern];
							}
							$tokens[] = $token;
						}
					}

					// get the handler from the second element
					$handler = array_shift($route);

					// create a complete route from the rest
					$route = array_merge([
						'methods' => ['GET', 'POST'],
						'domains' => ['*'],
						'handler' => $handler,
					], $route);

					// add the tokens the build map
					$this->map['build'][$name] = $tokens;

					// add the tokens the build map
					$this->map['routes'][$name] = $route;

					// create a match map
					$start = [];
					foreach($route['domains'] as $domain) {
						// chart domains
						if(! isset($this->map['match'][$domain])) {
							$this->map['match'][$domain] = [];
						}

						// chart methods and use them as the starting points
						foreach($route['methods'] as $method){
							$method = strtoupper($method);
							if(! isset($this->map['match'][$domain][$method])) {
								$this->map['match'][$domain][$method] = ['literal' => [], 'variable' => []];
							}
							$start[] =& $this->map['match'][$domain][$method];
						}
					}
					// get destinations an put the route in the destination
					foreach($this->chart($tokens, $start) as & $destination){
						// dont overwrite destinations that were already set by other routes
						if(! isset($destination['route']) || ! $destination['route']) {
							$destination['route'] = $name;
						}
					}
				}
			}
		}
		return $this->map;
	}


	/**
	 * Chart one or more paths for a route
	 * @param array $tokens
	 * @param array $parents
	 * @return array
	 */
	protected function chart($tokens, $parents)
	{
		// the assembled children
		$children = [];

		// get the first token
		if($token = array_shift($tokens)) {

			$child = [
				'route' => false,
				'literal' => [],
				'variable' => [],
			];

			if(! $token['literal']) {
				$child['name'] = $token['pattern'];
				$child['rule'] = $token['rule'];
			}

			// add the child to all the parents
			foreach($parents as & $parent) {
				if($token['literal']) {
					// only add it as literal if it didnt already exist
					if(! isset($parent['literal'][$token['pattern']])) {
						$parent['literal'][$token['pattern']] = $child;
					}
					// use the literal child as a new parent
					$children[] =& $parent['literal'][$token['pattern']];
				} else {
					// add to variable
					$parent['variable'][] = $child;
					// use a ref to the last added as a new parent
					// NB: dont use the $child var directly, as this will lead to unwanted refs
					$children[] =& $parent['variable'][count($parent['variable']) - 1];
				}

				// if token is optional, include the parent as a new parent
				// this creates a branching path
				if($token['optional']) {
					$children[] =& $parent;
				}
			}
			// recurse deeper with new set of children
			return $this->chart($tokens, $children);
		} else {
			// no tokens left, return the destinations
			return $parents;
		}
	}


	/**
	 * Try to match routes
	 * return matched route or false
	 * @return array|boolean
	 */
	public function match($path, $method = 'GET' , $domain = null)
	{
		// Get map
		$map = $this->map();

		// Get the match part
		$match = $map['match'];

		// Startobject for traversal
		$traverse = [];

		// Add available routes for the specific domain / method
		if ($domain !== null && isset($match[$domain]) && isset($match[$domain][$method])) {
			$traverse[] = $match[$domain][$method];
		}

		// Add routes that were declared for all domains
		if (isset($match['*']) && isset($match['*'][$method])) {
			$traverse[] = $match['*'][$method];
		}

		// Get string tokens from the path
		$tokens = explode($this->separator, trim($path, $this->separator));

		// First iteration. On the first iteration, all vars are set to [].
		$first = true;

		// Go through the available paths and keep the ones that are ok
		while($token = array_shift($tokens)){
			// fill up this array with matching paths for the next iteration
			$new = [];
			foreach($traverse as $item) {
				if($first) {
					// start with blank vars for each path
					$item['vars'] = [];
				}
				// match literal tokens
				if(isset($item['literal'][$token])) {
					// get the info
					$info = $item['literal'][$token];
					// carry over vars from parent
					$info['vars'] = $item['vars'];
					// add it to the new traverse
					$new[] = $info;
				}
				// match all variable tokens
				foreach($item['variable'] as $info) {
					if(! $info['rule'] ||  preg_match('/' . $info['rule'] . '/', $token)) {
						// carry over vars from parent
						$info['vars'] = $item['vars'];
						// add this var to it
						$info['vars'][$info['name']] = $token;
						// add it to the new traverse
						$new[] = $info;
					}
				}
			}
			// first iteration is over
			$first = false;

			// again, but with new starting point
			$traverse = $new;
		}

		// Check resulting destinations for having a route attached to it
		foreach($traverse as $info) {
			if($info['route'] && isset($map['routes'][$info['route']])) {
				// get the route and merge in the found vars
				return array_merge(
					$map['routes'][$info['route']],
					isset($info['vars']) ? $info['vars'] : []
				);
			}
		}
		// No match
		return false;
	}


	/**
	 * Create a path from a route and params
	 * @param string $route
	 * @param array $params
	 * @return string
	 */
	public function path($route, $params = [])
	{
		$map = $this->map();

		if(! array_key_exists($route, $map['build'])) {
			throw new Exception('Trying to build path with unmapped route: ' . $route);
		}

		$path = '';
		foreach($map['build'][$route] as $token) {
			if($token['literal']) {
				$path .= $token['pattern'] . $this->separator;
			} else {
				if(isset($params[$token['pattern']])) {
					$path .= $params[$token['pattern']] . $this->separator;
				} elseif(! $token['optional'] ) {
					throw new Exception('Trying to build path with route: ' . $route . ', but missing param: ' . $token['pattern']);
				}
			}
		}
		return trim($path, $this->separator);
	}
}