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

	/**
	 * Empty constructor
	 * Use it to pass in dependencies
	 */
	public function __construct() {}

	/**
	 * Return a description
	 * @return string
	 */
	public function description()
	{
		return '';
	}


	/**
	 * Return help text
	 * @return string
	 */
	public function help()
	{
		return '';
	}


	/**
	 * Do the actual handling of the command
	 * @return mixed
	 */
	public function handle() {}



	/**
	 * Startup of the command
	 */
	public function init()
	{
		$this->setDefinition(new InputDefinition());
		$this->setDescription($this->description());
		$this->setHelp($this->help());
	}


	/**
	 * Execution of the comand
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	public function execute(InputInterface $input, OutputInterface $output)
    {
		$this->input = $input;
		$this->output = $output;
		$this->handle();
    }


	/**
	 * Output of the command
	 * @param array $messages
	 */
	public function write($messages)
	{
		if(! is_array($messages)) {
			$messages = [$messages];
		}
		$this->output->writeln($messages);
	}
}