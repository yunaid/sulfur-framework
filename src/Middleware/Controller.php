<?php

namespace Sulfur\Middleware;

use Sulfur\Middleware\Contract as Middleware;

use Sulfur\Container;
use Sulfur\Request;
use Sulfur\Response;
use Exception;

class ControllerException extends Exception {}

class Controller implements Middleware
{

	/**
	 * Container instance
	 * @var Sulfur\Container
	 */
	protected $container;


	/**
	 * Create a controller
	 * @param Sulfur\Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}


	/**
	 * Run the middleware
	 * @param Sulfur\Request $request
	 * @param Sulfur\Response $response
	 * @param callable $next
	 * @return Sulfur\Response
	 */
	public function __invoke(
        Request $request,
        Response $response,
        callable $next
    ) {
		// get handler
		$handler = $request->get('handler');

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