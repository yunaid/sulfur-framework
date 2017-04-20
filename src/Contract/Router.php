<?php

namespace Sulfur\Contract;

interface Router
{
	public function run();

	public function set($name, $url);
	
	public function url();
}