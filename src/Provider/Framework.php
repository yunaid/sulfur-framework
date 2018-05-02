<?php

namespace Sulfur\Provider;

use Sulfur\Console;

class Framework
{
	public static function register($container)
	{
		$config = $container->get('Sulfur\Config');

		$container->set([
			// console
			'Sulfur\Console' => function() use ($container, $config) {
				$commands = [];
				foreach($config->console('commands', []) as $name => $class) {
					$commands[$name] = $container->get($class);
				}
				return new Console($commands);
			},
			//  logger
			'Sulfur\Logger' => [
				'Sulfur\Logger\Factory::make',
				'config' => [$config, ':resource' => 'logger'],
			],
			// fail middleware
			'Sulfur\Middleware\Fail' => [
				'config' => [$config, ':resource' => 'fail']
			],
			// router config
			'Sulfur\Router' => [
				'routes' => [$config, ':resource' => 'routes']
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