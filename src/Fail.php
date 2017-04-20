<?php

namespace Sulfur;

use Sulfur\Contract\Fail as Contract;

use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;

class Fail implements Contract
{
	const HANDLER_BROWSER = 'browser';
	const HANDLER_CALLABLE = 'callable';
	const HANDLER_REDIRECT = 'redirect';
	const HANDLER_NONE = 'none';

	protected $whoops;

	protected $registered = false;

	public function __construct()
	{
		$this->whoops = new \Whoops\Run();
	}


	public function handler($type, $handler = null)
	{
		switch($type) {
			case \Sulfur\Fail::HANDLER_BROWSER:
				$this->whoops->pushHandler(new PrettyPageHandler());
				break;
			case \Sulfur\Fail::HANDLER_CALLABLE:
				$this->whoops->pushHandler(function($e) use ($handler) {
					call_user_func($handler, $e);
				});
				break;
			case \Sulfur\Fail::HANDLER_REDIRECT:
				$this->whoops->pushHandler(function($e) use ($handler) {
					header('Location: ' . $handler);
					exit;
				});
				break;
		}
	}


	public function register()
	{
		if(! $this->registered){
			$this->registered = true;
			$this->whoops->register();
		}
	}
}