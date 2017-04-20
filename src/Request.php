<?php

namespace Sulfur;

use \Sulfur\Contract\Request as Contract;

use \Zend\Diactoros\ServerRequestFactory;

class Request implements Contract
{

	/**
	 * The raw request object
	 * @var \Zend\Diactoros\ServerRequest
	 */
	protected $request;

	protected $attributes = [];

	protected $handler = null;


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
		// create request
		$this->request = ServerRequestFactory::fromGlobals(
			$server ?: $_SERVER,
			$get ?: $_GET,
			$post ?: $_POST,
			$cookie ?: $_COOKIE,
			$files ?: $_FILES
		);
	}


	/**
	 * Get the raw request
	 * @return \Zend\Diactoros\Request
	 */
	public function raw()
	{
		return $this->request;
	}


	public function scheme()
	{
		return $this->request->getUri()->getScheme();
	}


	public function domain()
	{
		return $this->request->getUri()->getHost();
	}

	public function method()
	{
		return $this->request->getMethod();
	}

	public function port()
	{
		$port = $this->request->getUri()->getPort();
		if(! $port) {
			$port = 80;
		}
		return $port;
	}


	public function path()
	{
		return $this->request->getUri()->getPath();
	}


	public function query($name = null, $default = null)
	{
		$params = $this->request->getQueryParams();
		if($name !== null) {
			return isset($params[$name]) ? $params[$name] : $default;
		} else {
			return $params;
		}
	}

	public function qs()
	{
		return $this->request->getUri()->getQuery();
	}


	public function ajax()
	{
		return strtolower($this->request->getHeader('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
	}


	/**
	 * Set or get attributes
	 * @param array|null $attributes
	 * @return \Sulfur\Bridge\HTTP\Request|array
	 */
	public function attributes($attributes = null)
	{
		if($attributes === null) {
			return $this->attributes;
		} else {
			$this->attributes = array_merge($this->attributes, $attributes);
			return $this;
		}
	}


	/**
	 * Get or set the a handler variable
	 * @param mixed|null $handler
	 * @return \Sulfur\Bridge\HTTP\Request|mixed
	 */
	public function handler($handler = null)
	{
		if($handler === null) {
			return $this->handler;
		} else {
			$this->handler = $handler;
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
	 * @return \Sulfur\Bridge\HTTP\Request
	 */
	public function set($attribute, $value)
	{
		$this->attributes[$attribute] = $value;
		return $this;
	}
}