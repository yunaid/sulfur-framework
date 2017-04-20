<?php

namespace Sulfur;

use Sulfur\Contract\Response as Contract;

use \Zend\Diactoros\Response as BaseResponse;
use \Zend\Diactoros\Response\SapiEmitter;

class Response implements Contract
{
	/**
	 * PSR7 response
	 * @var \Zend\Diactoros\Response
	 */
	protected $response;

	/**
	 * Create a new response
	 */
	public function __construct()
	{
		$this->response = new BaseResponse();
	}

	/**
	 * Get the raw response object
	 * @return \Zend\Diactoros\Response
	 */
	public function raw()
	{
		return $this->response;
	}


	/**
	 * Helper to redirect to a url
	 * @param string $url
	 * @param int $status
	 */
	public function redirect($url, $status = 301)
	{
		$this->status($status);
		$this->header('location', $url);
		$this->send();
		exit;
	}

	
	/**
	 * Set status
	 * @param int $code
	 * @param string $message
	 * @return \Sulfur\Bridge\HTTP\Response
	 */
	public function status($code, $message = '')
	{
		$this->response = $this->response->withStatus($code, $message);
		return $this;
	}

	/**
	 * Add a header
	 * @param string $name
	 * @param string $value
	 * @return \Sulfur\Bridge\HTTP\Response
	 */
	public function header($name, $value)
	{
		$this->response = $this->response->withHeader($name, $value);
		return $this;
	}

	/**
	 * Append to body
	 * @param string $value
	 * @return \Sulfur\Bridge\HTTP\Response
	 */
	public function body($value)
	{
		$this->response->getBody()->write($value);
		return $this;
	}


	/**
	 * Send the status, headers and body to the browser
	 */
	public function send()
	{
		$emitter = new SapiEmitter();
		$emitter->emit($this->response);
	}
}