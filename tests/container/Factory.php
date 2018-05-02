<?php

class Factory
{
	public static function make($value = 'default')
	{
		return new Baz($value);
	}

	public function create($value = 'default')
	{
		return new Baz($value);
	}
}