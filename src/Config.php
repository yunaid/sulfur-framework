<?php

namespace Sulfur;

class Config
{
	/**
	 * Paths to load resources from
	 * @var array
	 */
	protected $paths = [];


	/**
	 * Replacements vars
	 * @var array
	 */
	protected $replace = [];

	/**
	 * Token for placeholders
	 * @var array
	 */
	protected $token = ['{{', '}}'];


	/**
	 * Pattern for placeholders
	 * @var array
	 */
	protected $pattern = '';


	/**
	 * Hot-cached resources
	 * @var array
	 */
	protected $resources = [];


	/**
	 * Resources available through get()
	 * @var array
	 */
	protected $loaded = [];


	/**
	 * Whether extra resources were loaded
	 * @var boolean
	 */
	protected $changed = false;


	/**
	 * Constructor
	 * @param string|array $paths
	 * @param array $cache
	 */
	public function __construct($paths, $replace = [], $token = null)
	{
		$this->paths = $paths;
		$this->replace = $replace;
		if(is_array($token) && count($token) >= 2) {
			$this->token[0] = $token[0];
			$this->token[1] = $token[1];
		}
		$this->pattern = '/' . preg_quote($this->token[0]) . '\s*([a-zA-Z0-9\-\_\.]+)\s*' . preg_quote($this->token[1]) . '/';
	}


	/**
	 * Get or set cached data
	 * @param array $resources
	 * @return mixed
	 */
	public function resources($resources = null)
	{
		if($resources === null) {
			return $this->resources;
		} else {
			$this->resources = array_merge($this->resources, $resources);
		}
	}


	/**
	 * Check whether extra resources were loaded (and should be cached)
	 * @return type
	 */
	public function changed()
	{
		return $this->changed;
	}


	/**
	 * Get a resource or a sub-key of a resource
	 * @param string $resource
	 * @param string|array $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function __invoke($resource, $key = null, $default = null)
	{
		return $this->resource($resource, $key, $default);
	}


	/**
	 * Get a resource or a sub-key of a resource
	 * @param string $resource
	 * @param string|array $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function resource($resource, $key = null, $default = null)
	{
		$data = $this->data($resource);
		if($key === null){
			return $data;
		} else {
			return $this->find($data, $key, $default);
		}
	}


	/**
	 * Get a resource by calling a method with it's name
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($method, $arguments)
	{
		return $this->__invoke(
			str_replace(['__', '_'], [':', DIRECTORY_SEPARATOR], $method),
			isset($arguments[0]) ? $arguments[0]: null,
			isset($arguments[1]) ? $arguments[1]: null
		);
	}


	/**
	 * Load resources available through ::get()
	 * Resources that are loaded later will act as a fallback
	 * @param array|string $resources
	 * @return $this
	 */
	public function load($resources = [])
	{
		if(! is_array($resources)) {
			$resources = [$resources];
		}
		foreach($resources as $resource) {
			// load data
			$this->data($resource);
			// mark it as loaded
			$this->loaded[] = $resource;
		}
		return $this;
	}


	/**
	 * Get a value from the loaded resources
	 * @param string|array $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key, $default = null, $replace = null)
	{
		foreach($this->loaded as $resource) {
			$found =  $this->find($this->resources[$resource], $key, false);
			if($found !== false) {
				return $found;
			}
		}
		return $default;
	}


	/**
	 * Get resource data
	 * @param string $resource
	 * @return array
	 */
	protected function data($resource)
	{
		if (isset($this->resources[$resource])) {
			return $this->resources[$resource];
		}

		$data = [];
		if(strpos($resource, ':') !== false) {
			// find a resource at a named path
			$parts = explode(':', $resource);
			if(isset($this->paths[$parts[0]])) {
				$full = $this->paths[$parts[0]] . $parts[1] . '.php';
				if (file_exists($full)) {
					$data = include($full);
				}
			}
		} else {
			// find the first available resource
			foreach ($this->paths as $path) {
				$full = $path . $resource . '.php';
				if (file_exists($full)) {
					$data = include($full);
					break;
				}
			}
		}

		// replace placeholders
		array_walk_recursive($data, function(&$item, $key) {
			if(is_string($item) && strpos($item, $this->token[0]) !== false) {
				$item = preg_replace_callback($this->pattern, function($matches) {
					if(isset($this->replace[$matches[1]])) {
						return $this->replace[$matches[1]];
					}
					return $matches[0];
				}, $item);
			}
		});

		$this->changed = true;
		$this->resources[$resource] = $data;
		return $data;
	}


	/**
	 * Get a variable from an array with dot notation or path-array
	 * @param array $data
	 * @param string|array $key
	 * @param mixed $default
	 * @return mixed
	 */
	protected function find($data, $key, $default = null)
	{
		// return all
		if ( ! $key) {
			return $data;
		}

		if (is_array($key)) {
			// key array provided
			// easy, keys with a dot are provided as-is in the array
			$walker = $data;
			while (count($key) > 0) {
				$part = array_shift($key);
				if (is_array($walker) && is_string($part) && isset($walker[$part])) {
					// go deeper
					$walker = $walker[$part];
				} else {
					// not here, done
					return $default;
				}
			}
			// return what we ended up with
			return $walker;
		} else {
			// dotted key provided
			// hard, because data keys can also contain dots
			$walker = $data;
			$base = '';
			$separator = '';
			$done = false;

			while (($parts = explode('.', $key, 2)) && ! $done) {
				// build a base key to look for in the current walker
				$base .= $separator . $parts[0];
				$separator = '.';

				if (is_array($walker) && isset($walker[$base])) {
					// base key found in current walker: go deeper
					$walker = $walker[$base];
					// reset the base key
					$separator = '';
					$base = '';
				}

				if (isset($parts[1])) {
					// more parts to find
					if (is_array($walker) && isset($walker[$parts[1]])) {
						// the rest of the path is entirely matched
						$walker = $walker[$parts[1]];
						// we are done: set base to '' to generate a succesful result
						$base = '';
						$done = true;
					} else {
						// part not found directly: start again with this part as the new path
						$key = $parts[1];
					}
				} else {
					// no more parts to find
					$done = true;
				}
			}
			if ($base === '') {
				// base is empty: path was found
				return $walker;
			} else {
				// base is not empty: path was not found
				return $default;
			}
		}
	}
}