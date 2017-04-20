<?php

namespace Sulfur\Contract;

interface Config
{
	public function __invoke($resource, $path = null, $default = null);

	public function __call($method, $arguments);

	public function load($resources = []);

	public function get($key, $default = null);
}