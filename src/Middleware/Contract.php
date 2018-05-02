<?php

namespace Sulfur\Middleware;

use Sulfur\Request;
use Sulfur\Response;

interface Contract
{
	public function __invoke(Request $request, Response $response, callable $next);
}