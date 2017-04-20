<?php

namespace Sulfur\Contract;

interface Fail
{
	public function handler($type, $handler = null);

	public function register();
}