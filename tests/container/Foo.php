<?php


class Foo
{
	public function __construct(Bar $bar, Baz $baz, $value = 'default')
	{
		$this->bar = $bar;
		$this->baz = $baz;
		$this->value = $value;
	}

	public function barValue()
	{
		return $this->bar->value();
	}

	public function bazValue()
	{
		return $this->baz->value();
	}

	public function value()
	{
		return $this->value;
	}
}