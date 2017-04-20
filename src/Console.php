<?php

namespace Sulfur;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Console
{
	public function __construct($commands)
    {
        $this->application = new Application();
		foreach($commands as $name => $command) {
			$command->setName($name);
			$command->init();
			$this->application->add($command);
		}
		$this->application->run();
    }
}