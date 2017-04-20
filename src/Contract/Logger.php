<?php

namespace Sulfur\Contract;

interface Logger
{
	public function debug($message = '', array $args = []);

	public function info($message = '', array $args = []);

	public function notice($message = '', array $args = []);

	public function warning($message = '', array $args = []);

	public function error($message = '', array $args = []);

	public function critical($message = '', array $args = []);

	public function alert($message = '', array $args = []);

	public function emergeny($message = '', array $args = []);

	public function message($level = 100, $message = '', array $args = []);
}