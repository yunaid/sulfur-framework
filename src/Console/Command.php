<?php

namespace Sulfur\Console;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;


class Command extends BaseCommand
{

	protected $input;

	protected $output;

	public function __construct(){}


	public function init()
	{
		$this->setDefinition(new InputDefinition());
		$this->setDescription($this->description());
		$this->setHelp($this->help());
	}

	public function description(){
		return '';
	}

	public function help(){
		return '';
	}

	public function handle(){

	}

	public function execute(InputInterface $input, OutputInterface $output)
    {
		$this->input = $input;
		$this->output = $output;
		$this->handle();
    }


	public function write($messages)
	{
		if(! is_array($messages)) {
			$messages = [$messages];
		}
		$this->output->writeln($messages);
	}
}