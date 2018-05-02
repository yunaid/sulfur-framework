<?php

namespace Sulfur;

use \Zend\Diactoros\ServerRequestFactory;

class Request
{

	/**
	 * The raw request object
	 * @var \Zend\Diactoros\ServerRequest
	 */
	protected $request;

	/**
	 * Bag of extra attributes
	 * @var array
	 */
	protected $attributes = [];


	/**
	 * Post data we are using
	 * @var array
	 */
	protected $post = [];


	/**
	 * Create a new request
	 * @param array $server
	 * @param array $get
	 * @param array $post
	 * @param array $cookie
	 * @param array $files
	 */
	public function __construct(
		$server = null,
		$get = null,
		$post = null,
		$cookie = null,
		$files = null
	)
	{
		// create postdata
		$this->post = $post ?: $_POST;

		// create request
		$this->request = ServerRequestFactory::fromGlobals(
			$server ?: $_SERVER,
			$get ?: $_GET,
			$this->post,
			$cookie ?: $_COOKIE,
			$files ?: $_FILES
		);
	}


	/**
	 * Get the http / https
	 * @return string
	 */
	public function scheme()
	{
		return $this->request->getUri()->getScheme();
	}


	/**
	 * Get the domain
	 * @return string
	 */
	public function domain()
	{
		return $this->request->getUri()->getHost();
	}


	/**
	 * Get request method
	 * @return string
	 */
	public function method()
	{
		return $this->request->getMethod();
	}


	/**
	 * Get the port
	 * @return int
	 */
	public function port()
	{
		$port = $this->request->getUri()->getPort();
		if(! $port) {
			$port = 80;
		}
		return $port;
	}


	/**
	 * Get the absolute path up to the executing front controller (index.php)
	 * Used to prefix routes with this base path, so site can run somewhere else than the root
	 * @return string
	 */
	public function base()
	{
		$params = $this->request->getServerParams();

		if (isset($params['SCRIPT_NAME'])) {
			$base = dirname($params['SCRIPT_NAME']);
		} elseif(isset($params['SCRIPT_FILENAME']) && isset($params['DOCUMENT_ROOT'])) {
			$base = dirname(str_replace($params['DOCUMENT_ROOT'], '', $params['SCRIPT_FILENAME']));
		} elseif(isset($params['PHP_SELF'])) {
			$base = dirname($params['PHP_SELF']);
			if(substr($base, -4) == '.php') {
				$base = dirname($base);
			}
		} else {
			$base = '';
 		}

		$base = '/' . trim($base, '/');

		if($base === '/') {
			$base = '';
		}
		return $base;
	}



	/**
	 * Get the request path
	 * @param boolean $full Return path including base
	 * @return string
	 */
	public function path($full = true)
	{
		$path = $this->request->getUri()->getPath();

		if($full) {
			return $path;
		} else {
			$base = $this->base();
			if(strpos($path, $base) === 0) {
				$path = str_replace($base, '', $path);
			}
			return $path;
		}
	}


	/**
	 * Get a query string var
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public function query($name = null, $default = null)
	{
		$params = $this->request->getQueryParams();
		if($name !== null) {
			return isset($params[$name]) ? $params[$name] : $default;
		} else {
			return $params;
		}
	}


	/**
	 * Get a post var
	 * @param string $name
	 * @param string|array $default
	 * @return string|array
	 */
	public function post($name = null, $default = null)
	{
		if($name !== null) {
			return isset($this->post[$name]) ? $this->post[$name] : $default;
		} else {
			return $this->post;
		}
	}


	/**
	 * Get the whole query string
	 * @return string
	 */
	public function qs()
	{
		return $this->request->getUri()->getQuery();
	}


	/**
	 * Whether this the request is via ajax
	 * @return boolean
	 */
	public function ajax()
	{
		return strtolower($this->request->getHeader('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
	}


	/**
	 * Get info about a posted file
	 * @param string $name
	 * @return strClass|null
	 */
	public function file($name)
	{
		$files = $this->request->getUploadedFiles();
		if(isset($files[$name])) {
			return (object) [
				'path' => $files[$name]['tmp_name'],
				'name' => $files[$name]['name']
			];
		} else {
			return null;
		}
	}


	/**
	 * Set or get attributes
	 * @param array|null $attributes
	 * @return Sulfur\Request|array
	 */
	public function attributes(array $attributes = null)
	{
		if($attributes === null) {
			return $this->attributes;
		} elseif(is_array($attributes)) {
			$this->attributes = array_merge($this->attributes, $attributes);
			return $this;
		}
	}


	/**
	 * Get an attribute
	 * @param string $attribute
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($attribute, $default = null)
	{
		if(isset($this->attributes[$attribute])) {
			return $this->attributes[$attribute];
		} else {
			return $default;
		}
	}


	/**
	 * Set an attribute
	 * @param string $attribute
	 * @param mixed $value
	 * @return \Sulfur\\Request
	 */
	public function set($attribute, $value)
	{
		$this->attributes[$attribute] = $value;
		return $this;
	}
}