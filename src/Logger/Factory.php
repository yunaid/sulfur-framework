<?php

namespace Sulfur\Logger;

use Sulfur\Logger;
use Sulfur\Logger\Handlers;

/**
 * Responsible for making only one of each logger type
 */
class Factory
{
	protected static $loggers = [];

	public static function make(array $config, $name = null)
	{
		if($name === null) {
			$name = key($config);
		}
		$config = $config[$name];

		if(!isset(self::$loggers[$name])) {
			self::$loggers[$name] = new Logger(new Handlers($name, $config));
		}

		return self::$loggers[$name];
	}
}