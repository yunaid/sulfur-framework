<?php

namespace Sulfur\Middleware;

use Sulfur\Contract\Middleware;

use Sulfur\Contract\Logger;
use Sulfur\Contract\Fail as FailHandler;
use Sulfur\Contract\Request;
use Sulfur\Contract\Response;

class Fail implements Middleware
{

	/**
	 * Fail instance
	 * @var Sulfur\Contract\Fail
	 */
	protected $fail;

	/**
	 * Logger instance
	 * @var Sulfur\Contract\Logger
	 */
	protected $logger;


	/**
	 * Create the fail middleware
	 * @param Sulfur\Contract\Fail $fail
	 * @param Sulfur\Contract\Logger $logger
	 * @param array $config
	 */
	public function __construct(FailHandler $fail, Logger $logger, array $config = [] )
	{
		$this->fail = $fail;
		$this->logger = $logger;
		$this->config = $config;
	}

	/**
	 * Run the middleware
	 * @param \Sulfur\Contract\Request $request
	 * @param \Sulfur\Contract\Response $response
	 * @param \callable $next
	 * @return \Sulfur\Contract\Response
	 */
	public function __invoke(
		Request $request,
		Response $response,
		callable $next
	)
	{
		// register the actual handler
		// this will be done second
		$this->fail->handler($this->config['type'], $this->config['handler']);

		// register the logger
		// this will be done first
		$this->fail->handler(\Sulfur\Fail::HANDLER_CALLABLE, function($e){
			$this->logger->error($e->getMessage(), ['e' => $e]);
		});

		// let the handler register itself
		$this->fail->register();

		// go on
		return $next($request, $response);
	}

}
