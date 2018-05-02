<?php

namespace Sulfur;

use Sulfur\Logger\Handlers;

class Logger {
	const HANDLER_FILE = 'file';
	const HANDLER_SYSLOG = 'syslog';
	const HANDLER_MAIL = 'mail';

	const FORMAT_LINE = 'line';
	const FORMAT_HTML = 'html';

	// https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md
	const LEVEL_DEBUG = 100;
	const LEVEL_INFO = 200;
	const LEVEL_NOTICE = 250;
	const LEVEL_WARNING = 300;
	const LEVEL_ERROR = 400;
	const LEVEL_CRITICAL = 500;
	const LEVEL_ALERT = 550;
	const LEVEL_EMERGENCY = 600;

	protected $handlers;

	public function __construct(Handlers $handlers)
	{
		$this->handlers = $handlers;
	}

	public function debug($message = '', array $args = [])
	{
		$this->handlers->handle(self::LEVEL_DEBUG, $message, $args);
	}

	public function info($message = '', array $args = [])
	{
		$this->handlers->handle(self::LEVEL_INFO, $message, $args);
	}

	public function notice($message = '', array $args = [])
	{
		$this->handlers->handle(self::LEVEL_NOTICE, $message, $args);
	}

	public function warning($message = '', array $args = [])
	{
		$this->handlers->handle(self::LEVEL_WARNING, $message, $args);
	}

	public function error($message = '', array $args = [])
	{
		$this->handlers->handle(self::LEVEL_ERROR, $message, $args);
	}

	public function critical($message = '', array $args = [])
	{
		$this->handlers->handle(self::LEVEL_CRITICAL, $message, $args);
	}

	public function alert($message = '', array $args = [])
	{
		$this->handlers->handle(self::LEVEL_ALERT, $message, $args);
	}

	public function emergeny($message = '', array $args = [])
	{
		$this->handlers->handle(self::LEVEL_EMERGENCY, $message, $args);
	}

	public function message($level = 100, $message = '', array $args = [])
	{
		$this->handlers->handle($level, $message, $args);
	}
}