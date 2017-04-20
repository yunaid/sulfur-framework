<?php

namespace Sulfur\Contract;

interface LoggerHandlers
{
	public function handle($level, $message = '', array $args = []);
}