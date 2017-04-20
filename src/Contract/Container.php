<?php

namespace Sulfur\Contract;

interface Container
{
	public function set($nameOrDefinitions, $definition = null);

	public function share($nameOrNames, $definition = null);

	public function get($name, array $args = []);

	public function call($callable, array $args = [], $context = null);
}