<?php

namespace Sulfur\Middleware;

use Sulfur\Contract\Middleware as Contract;

use Sulfur\Contract\Container;
use Sulfur\Contract\Request;
use Sulfur\Contract\Response;

class ControllerException extends \Exception {}

class Controller implements Contract
{

	/**
	 * Container instance
	 * @var Sulfur\Contract\Container
	 */
	protected $container;


	/**
	 * Create a controller
	 * @param \Sulfur\Contract\Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}


	/**
	 * Run the middleware
	 * @param \Sulfur\Contract\Request $request
	 * @param \Sulfur\Contract\Response $response
	 * @param \callable $next
	 * @return \Sulfur\Contract\Response
	 * @throws ControllerException
	 */
	public function __invoke(
        Request $request,
        Response $response,
        callable $next
    ) {
		// get handler
		$handler = $request->handler();

		// create & execute a controller-action
		if($handler && strpos($handler, '@') !== false){

			$parts = explode('@', $handler);
			$controller =  $this->container->get($parts[0]);

			$attributes = $request->attributes();
			$args = [
				':request' => $request,
				':response' => $response
			];
			foreach($attributes as $name => $value){
				$args[':' . $name] = $value;
			}

			$result = $this->container->call([$controller, $parts[1]], $args, $handler);
			if($result === false) {
				return $response;
			} else {
				$response->body($result);
				return $next($request, $response);
			}
		} else {
			return $next($request, $response);
		}
	}
}