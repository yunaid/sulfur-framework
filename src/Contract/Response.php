<?php

namespace Sulfur\Contract;

interface Response
{
	public function raw();

	public function status($code, $message = '');

	public function header($name, $value);

	public function body($value);

	public function redirect($url, $status = 301);

	public function send();
}