<?php

namespace Sulfur\Contract;

use Sulfur\Contract\Request;
use Sulfur\Contract\Response;

interface Middleware
{
	public function __invoke(Request $request, Response $response, callable $next);
}