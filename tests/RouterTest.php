<?php

class RouterTest extends PHPUnit_Framework_TestCase
{

	public function testBasic()
	{
		// assert basic pattern matching
		$router = new Sulfur\Router([
			'name' => ['path', 'handler']
		]);
		$this->assertEquals('handler', $router->match('path')['handler']);
		$this->assertEquals('handler', $router->match('path/')['handler']);
		$this->assertEquals('handler', $router->match('/path/')['handler']);
		$this->assertEquals('handler', $router->match('/path')['handler']);
		$this->assertEquals(false, $router->match('otherpath'));

		// surrounding slashes dont matter
		$router = new Sulfur\Router([
			'name' => ['/path/', 'handler']
		]);
		$this->assertEquals('handler', $router->match('path')['handler']);
		$this->assertEquals('handler', $router->match('path/')['handler']);
		$this->assertEquals('handler', $router->match('/path/')['handler']);
		$this->assertEquals('handler', $router->match('/path')['handler']);
		$this->assertEquals(false, $router->match('otherpath'));


		// match empty
		$router = new Sulfur\Router([
			'name' => ['', 'handler']
		]);
		$this->assertEquals('handler', $router->match('')['handler']);
		$this->assertEquals('handler', $router->match('/')['handler']);
		$this->assertEquals('handler', $router->match('/////')['handler']);
		$this->assertEquals(false, $router->match(' '));
		$this->assertEquals(false, $router->match('/ '));

		// match slash
		$router = new Sulfur\Router([
			'name' => ['/', 'handler']
		]);
		$this->assertEquals('handler', $router->match('')['handler']);
		$this->assertEquals('handler', $router->match('/')['handler']);
		$this->assertEquals('handler', $router->match('/////')['handler']);
		$this->assertEquals(false, $router->match(' '));
		$this->assertEquals(false, $router->match('/ '));

		// match multiple parts
		$router = new Sulfur\Router([
			'name' => ['foo/bar', 'handler']
		]);
		$this->assertEquals('handler', $router->match('foo/bar')['handler']);
		$this->assertEquals('handler', $router->match('/foo/bar')['handler']);
		$this->assertEquals('handler', $router->match('/foo/bar/')['handler']);
		$this->assertEquals('handler', $router->match('//////foo/bar//////')['handler']);
		$this->assertEquals(false, $router->match('bar/foo'));
		$this->assertEquals(false, $router->match('foo//bar'));
	}


	public function testOptional()
	{
		$router = new Sulfur\Router([
			'name' => ['foo/(bar)', 'handler']
		]);
		$this->assertEquals('handler', $router->match('foo/bar')['handler']);
		$this->assertEquals('handler', $router->match('foo/')['handler']);
		$this->assertEquals(false, $router->match('bar'));

		$router = new Sulfur\Router([
			'name' => ['foo/(bar)/(baz)/(qux)/part', 'handler']
		]);
		$this->assertEquals('handler', $router->match('foo/part')['handler']);
		$this->assertEquals('handler', $router->match('foo/bar/part')['handler']);
		$this->assertEquals('handler', $router->match('foo/baz/part')['handler']);
		$this->assertEquals('handler', $router->match('foo/qux/part')['handler']);
		$this->assertEquals('handler', $router->match('foo/bar/qux/part')['handler']);
		$this->assertEquals('handler', $router->match('foo/baz/qux/part')['handler']);
		$this->assertEquals('handler', $router->match('foo/bar/baz/qux/part')['handler']);
		$this->assertEquals(false, $router->match('foo'));
		$this->assertEquals(false, $router->match('foo/bar/baz/qux'));
		$this->assertEquals(false, $router->match('foo/baz/bar/part'));
		$this->assertEquals(false, $router->match('foo/foo/part'));
	}



	public function testMethods()
	{
		$router = new Sulfur\Router([
			'name' => ['path', 'handler', 'methods' => ['PUT', 'DELETE']]
		]);
		$this->assertEquals(false, $router->match('path'));
		$this->assertEquals(false, $router->match('path', 'POST'));
		$this->assertEquals('handler', $router->match('path', 'PUT')['handler']);
		$this->assertEquals('handler', $router->match('path', 'DELETE')['handler']);
	}


	public function testDomains()
	{
		$router = new Sulfur\Router([
			'name' => ['path', 'handler', 'domains' => ['foo.com', 'bar.com']]
		]);

		$this->assertEquals(false, $router->match('path'));
		$this->assertEquals(false, $router->match('path', 'GET', 'baz.com'));
		$this->assertEquals('handler', $router->match('path', 'GET', 'foo.com')['handler']);
		$this->assertEquals('handler', $router->match('path', 'GET', 'bar.com')['handler']);
	}


	public function testAttributes()
	{
		// extra attributes
		$router = new Sulfur\Router([
			'name' => ['path', 'handler', 'var' => 'val']
		]);
		$this->assertEquals('val', $router->match('path')['var']);

		// overwerite handler
		$router = new Sulfur\Router([
			'name' => ['path', 'handler', 'handler' => 'handler2']
		]);
		$this->assertEquals('handler2', $router->match('path')['handler']);
	}



	public function testVariables()
	{

		$router = new Sulfur\Router([
			'name' => ['foo/:bar', 'handler']
		]);
		$this->assertEquals('42', $router->match('foo/42')['bar']);


		$router = new Sulfur\Router([
			'name' => ['foo/:bar/baz', 'handler']
		]);
		$this->assertEquals('42', $router->match('foo/42/baz')['bar']);


		$router = new Sulfur\Router([
			'name' => ['foo/(:bar)/baz', 'handler']
		]);
		$this->assertEquals('42', $router->match('foo/42/baz')['bar']);
		$this->assertArrayNotHasKey('bar', $router->match('foo/baz'));

		$router = new Sulfur\Router([
			'name' => ['foo/(:bar)/baz', 'handler', 'bar' => '41']
		]);

		$this->assertEquals('42', $router->match('foo/42/baz')['bar']);
		$this->assertEquals('41', $router->match('foo/baz')['bar']);
	}



	public function testRules()
	{
		$router = new Sulfur\Router([
			'name' => ['foo/:bar', 'handler', 'rules' => ['bar' => '[0-9]+']]
		]);
		$this->assertEquals(false, $router->match('foo/bar'));
		$this->assertEquals(false, $router->match('foo'));
		$this->assertEquals('42', $router->match('foo/42')['bar']);

		$router = new Sulfur\Router([
			'name' => ['foo/(:bar)', 'handler', 'rules' => ['bar' => '[0-9]+']]
		]);
		$this->assertEquals(false, $router->match('foo/bar'));
		$this->assertEquals('handler', $router->match('foo')['handler']);
		$this->assertEquals('42', $router->match('foo/42')['bar']);


		$router = new Sulfur\Router([
			'name' => ['foo/(:bar)', 'handler', 'rules' => ['bar' => '[0-9]+'], 'bar' => 41]
		]);
		$this->assertEquals(false, $router->match('foo/bar'));
		$this->assertEquals('41', $router->match('foo')['bar']);
		$this->assertEquals('42', $router->match('foo/42')['bar']);


		$router = new Sulfur\Router([
			'name' => ['foo/:bar', 'handler', 'rules' => ['bar' => '(var1|var2)']]
		]);
		$this->assertEquals(false, $router->match('foo/var3'));
		$this->assertEquals('var1', $router->match('foo/var1')['bar']);
		$this->assertEquals('var2', $router->match('foo/var2')['bar']);
	}


	public function testBuild()
	{
		$router = new Sulfur\Router([
			'name' => ['foo/:bar/:baz', 'handler']
		]);
		$this->assertEquals('foo/1/2', $router->path('name',['bar'=>1, 'baz' => 2]));
		$this->assertEquals('foo/1/2', $router->path('name',['baz' => 2, 'bar'=>1]));

		$router = new Sulfur\Router([
			'name' => ['foo/(:bar)/:baz', 'handler']
		]);
		$this->assertEquals('foo/1/2', $router->path('name',['bar'=>1, 'baz' => 2]));
		$this->assertEquals('foo/2', $router->path('name',['baz' => 2]));
	}



	public function testMissingParam()
	{
		$router = new Sulfur\Router([
			'name' => ['foo/:bar/:baz', 'handler']
		]);
		try {
			$router->path('name',['bar' => 1]);
		} catch (Exception $e) {
			return;
		}
		$this->fail('Missing param exception not thrown');
	}


	public function testMissingRoute()
	{
		$router = new Sulfur\Router([
			'name' => ['foo', 'handler']
		]);
		try {
			$router->path('name2');
		} catch (Exception $e) {
			return;
		}
		$this->fail('Missing route exception not thrown');
	}


	public function testOrder()
	{
		// first valid route is used
		$router = new Sulfur\Router([
			'order1' => ['foo', 'handler1'],
			'order2' => ['foo/(bar)', 'handler2']
		]);
		$this->assertEquals('handler1', $router->match('foo')['handler']);
		$this->assertEquals('handler2', $router->match('foo/bar')['handler']);

		$router = new Sulfur\Router([
			'name1' => ['path', 'handler1'],
			'name2' => ['path', 'handler2']
		]);
		$this->assertEquals('handler1', $router->match('path')['handler']);


		$router = new Sulfur\Router([
			'name1' => ['(foo)', 'handler1'],
			'name2' => ['(foo)/(bar)', 'handler2']
		]);
		$this->assertEquals('handler1', $router->match('')['handler']);
		$this->assertEquals('handler1', $router->match('foo')['handler']);
		$this->assertEquals('handler2', $router->match('bar')['handler']);
		$this->assertEquals('handler2', $router->match('foo/bar')['handler']);
	}


	public function testSeparator()
	{
		// different separator
		$router = new Sulfur\Router([
			'name' => ['foo|(bar)|baz', 'handler']
		], '|' );
		$this->assertEquals('handler', $router->match('foo|baz')['handler']);
	}



	public function testMap()
	{
		$router = new Sulfur\Router([
			'name' => ['path', 'handler']
		]);
		$map = $router->map();

		// new router that uses map
		$router = new Sulfur\Router();
		$this->assertEquals(false, $router->match('path'));

		$router->map($map);
		$this->assertEquals('handler', $router->match('path')['handler']);
	}
}