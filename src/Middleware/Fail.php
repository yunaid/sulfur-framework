<?php

namespace Sulfur\Middleware;

use Sulfur\Middleware\Contract as Middleware;

use Sulfur\Container;
use Sulfur\Request;
use Sulfur\Response;

use Whoops\Run as Whoops;
use Whoops\Handler\PrettyPageHandler;
use Exception;
use ErrorException;

class Fail implements Middleware
{

	static $handling = false;

	const HANDLER_DEBUG = 'debug';
	const HANDLER_PAGE = 'page';
	const HANDLER_REDIRECT = 'redirect';
	const HANDLER_NONE = 'none';

	protected $container;

	protected $config = [
		'handler' => 'none',
		'redirect' => null,
		'page' => null,
	];


	/**
	 * Create the fail middleware
	 * @param Sulfur\Container
	 * @param array $config
	 */
	public function __construct(Container $container, array $config = [] )
	{
		$this->container = $container;
		$this->config = array_merge($this->config, $config);
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
	)
	{
		if ($this->config['handler'] === self::HANDLER_DEBUG) {
			$whoops = new Whoops();
			$whoops->pushHandler(new PrettyPageHandler());
			$whoops->pushHandler(function($e) {
				$this->log($e);
			});
			$whoops->register();
		} else {
			$this->register();
		}
		return $next($request, $response);
	}


	/**
	 * Register error handlers for production (not using Whoops)
	 * Catch exceptions, errors and everything that falls through
	 */
	protected function register()
	{
        set_exception_handler(function($e) {
			$this->handle($e);
		});

		set_error_handler(function($level, $message, $file = null, $line = null) {
            $this->handle(new ErrorException($message,  $level,  $level, $file, $line));
			// dont propagate the error to the sutdown function
			return true;
		}, E_ALL | E_STRICT);

		register_shutdown_function(function() {
			$error = error_get_last();
			if($error) {
				$this->handle(new ErrorException( $error['message'],  $error['type'],  $error['type'], $error['file'], $error['line']));
			}
		});
	}


	/**
	 * Handle an exception
	 * @param Exception $e
	 */
	protected function handle(Exception $e)
	{
		if (self::$handling) {
			// dont double handle
			exit(1);
		} else {

			self::$handling = true;

			// log the exception
			$this->log($e);

			// dont cache this response
			if (! headers_sent() ) {
				header('Cache-Control: no-cache, no-store, must-revalidate');
				header('Pragma: no-cache');
				header('Expires: 0');
			}

			// remove output buffering
			while (ob_get_level()) {
				ob_end_clean();
			}

			switch($this->config['handler']) {
				case self::HANDLER_PAGE:
					// show a static page
					if(file_exists($this->config['page'])) {
						echo file_get_contents($this->config['page']);
					}
					break;
				case self::HANDLER_REDIRECT:
					// redirect to an error page
					if($this->config['redirect'] && ! headers_sent()) {
						header('Location: ' . $this->config['redirect'], true, 307);
					}
					break;
			}
		}
		exit(1);
	}


	/**
	 * Log an exception or error
	 * Get the logger through the container here and not by constructor injection
	 * We dont need the logger overhead when there's no error
	 * @param $e
	 */
	protected function log($e)
	{
		$logger = $this->container->get('Sulfur\Logger');
		$logger->error($e->getMessage(), ['e' => $e]);
	}
}
