<?php

namespace Sulfur\Contract;

interface Request
{
	public function raw();

	public function scheme();

	public function domain();

	public function port();

	public function path();

	public function query($name = null, $default = null);

	public function qs();

	public function ajax();

	public function attributes($attributes = null);

	public function handler($handler = null);

	public function set($attribute, $value);

	public function get($attribute, $default = null);
}