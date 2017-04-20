<?php

namespace Sulfur\Provider;

class Framework
{
	public static function register($container)
	{
		$config = $container->get('Sulfur\Config');

		$container->set([
			// implementation of interfaces
			'@Sulfur\Contract\Config' => 'Sulfur\Config',
			'@Sulfur\Contract\Container' => 'Sulfur\Container',
			'@Sulfur\Contract\Fail' => 'Sulfur\Fail',
			'@Sulfur\Contract\Logger' => 'Sulfur\Logger',
			'@Sulfur\Contract\LoggerHandlers' => 'Sulfur\Logger\Handlers',
			'@Sulfur\Contract\Request' => 'Sulfur\Request',
			'@Sulfur\Contract\Response' => 'Sulfur\Response',
			'@Sulfur\Contract\Router' => 'Sulfur\Router',

			// console
			'~Sulfur\Console' => function() use ($container, $config) {
				$commands = [];
				foreach($config->console('commands') as $name => $class) {
					$commands[$name] = $container->get($class);
				}
				return new \Sulfur\Console($commands);
			},

			//  logger
			'~Sulfur\Logger' => [
				'\Sulfur\Logger\Factory::make',
				'~config' => [$config, ':resource' => 'logger'],
			],
			// fail middleware
			'Sulfur\Middleware\Fail' => [
				'@logger' => ['Sulfur\Logger', ':name' => 'fail'],
				'~config' => [$config, ':resource' => 'fail']
			],
			// router config
			'Sulfur\Router' => [
				'~config' => [$config, ':resource' => 'router']
			],


		]);

		$container->share([
			'Sulfur\Config',
			'Sulfur\Container',
			'Sulfur\Request',
			'Sulfur\Router',
		]);
	}
}