<?php


class Baz
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