<?php

class Bar
{
	public function __construct($value = 'default')
	{
		$this->value = $value;
	}

	public function value()
	{
		return $this->value;
	}
}