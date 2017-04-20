<?php

namespace Sulfur\Logger;

use Sulfur\Contract\LoggerHandlers as Contract;
use Sulfur\Logger;

use Monolog\Logger as BaseLogger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\HtmlFormatter;

class Handlers implements Contract
{
	/**
	 * The monolog instance
	 * @var \Monolog\Logger
	 */
	protected $logger;


	/**
	 * Constructor
	 * @param string $group
	 * @param array $config
	 */
	public function __construct($group, array $config = [])
	{
		$this->logger = new BaseLogger($group);

		foreach($config as $type => $handlerConfig){
			$handler = false;
			if($handlerConfig['active']) {
				switch($type) {
					case Logger::HANDLER_FILE:
						$file = $handlerConfig['path'];
						$max = isset($handlerConfig['max']) ? $handlerConfig['max'] : 100;
						$level = isset($handlerConfig['level']) ? $handlerConfig['level'] : 200;
						$handler = new RotatingFileHandler($file, $max, $level);
						break;

					case Logger::HANDLER_SYSLOG:
						$file = $handlerConfig['path'];
						$level = isset($handlerConfig['level']) ? $handlerConfig['level'] : 200;
						$id = isset($handlerConfig['id']) ? $handlerConfig['id'] : 'sulfur-logger';
						$handler = new SyslogHandler($id, LOG_USER, $level);
						break;

					case Logger::HANDLER_MAIL:
						$to = $handlerConfig['to'];
						$subject= $handlerConfig['subject'];
						$from = $handlerConfig['from'];
						$level = isset($handlerConfig['level']) ? $handlerConfig['level'] : 200;
						$handler = new NativeMailerHandler($to, $subject, $from, $level);
						break;
				}
			}

			if($handler) {
				if(isset($handlerConfig['format'])){
					$formatter = false;
					switch($handlerConfig['format']) {
						case Logger::FORMAT_LINE:
							$formatter = new LineFormatter();
							break;
						case Logger::FORMAT_HTML:
							$formatter = new HtmlFormatter();
							break;
					}
					if($formatter) {
						$handler->setFormatter($formatter);
					}
				}
				$this->logger->pushHandler($handler);
			}
		}
	}


	/**
	 * Handle a logged message
	 * @param int $level
	 * @param string $message
	 * @param array $args
	 */
	public function handle($level, $message = '', array $args = [])
	{
		$this->logger->addRecord($level, $message, $args);
	}
}