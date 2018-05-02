<?php

include_once __DIR__ . '/container/Foo.php';
include_once __DIR__ . '/container/Bar.php';
include_once __DIR__ . '/container/Baz.php';
include_once __DIR__ . '/container/Factory.php';

class ContainerTest extends PHPUnit_Framework_TestCase
{

	protected function make()
	{
		return new \Sulfur\Container;
	}


	public function testBasicClass()
	{
		$container = $this->make();

		// resolve class with default constructor param
		$baz = $container->get('Baz');
		$this->assertEquals(true, $baz instanceof Baz);
		$this->assertEquals('default', $baz->value());

		// resolve class with given literal constructor param
		$baz = $container->get('Baz', [':value' => 'notdefault']);
		$this->assertEquals('notdefault', $baz->value());

		// resolve class with given resolving constructor param
		$baz = $container->get('Baz', ['value' => function() {
				return 'resolved';
			}]);
		$this->assertEquals('resolved', $baz->value());
	}


	public function testProviders()
	{
		// Set empty resolver
		$container = $this->make();
		$container->set('Baz');
		$baz = $container->get('Baz');
		$this->assertEquals(true, $baz instanceof Baz);
		$this->assertEquals('default', $baz->value());

		// Set resolver consisting of default values
		$container = $this->make();
		$container->set('Baz', [':value' => 'notdefault']);
		$baz = $container->get('Baz');
		$this->assertEquals('notdefault', $baz->value());

		// Set resolver consisting of factory string
		$container = $this->make();
		$container->set('Baz', 'Factory::make');
		$baz = $container->get('Baz');
		$this->assertEquals(true, $baz instanceof Baz);

		// Set resolver consisting of factory string with default value
		$container = $this->make();
		$container->set('Baz', ['Factory::make', ':value' => 'notdefault']);
		$baz = $container->get('Baz');
		$this->assertEquals(true, $baz instanceof Baz);
		$this->assertEquals('notdefault', $baz->value());

		// provide an argument for the factory
		$baz = $container->get('Baz', [':value' => 'alsonotdefault']);
		$this->assertEquals('alsonotdefault', $baz->value());


		// Set resolver consisting of closure
		$container = $this->make();
		$container->set('Baz', function() {
			return new Baz();
		});
		$baz = $container->get('Baz');
		$this->assertEquals(true, $baz instanceof Baz);


		// Set resolver consisting of closure and default value
		$container = $this->make();
		$container->set('Baz', [function($value) {
				return new Baz($value);
			}, ':value' => 'notdefault']);
		$baz = $container->get('Baz');
		$this->assertEquals(true, $baz instanceof Baz);
		$this->assertEquals('notdefault', $baz->value());

		// Overwrite the default value with a literal
		$baz = $container->get('Baz', [':value' => 'alsonotdefault']);
		$this->assertEquals(true, $baz instanceof Baz);
		$this->assertEquals('alsonotdefault', $baz->value());

		// Overwrite the default value with a resolver
		$baz = $container->get('Baz', ['value' => function() {
				return 'resolvedvalue';
			}]);
		$this->assertEquals(true, $baz instanceof Baz);
		$this->assertEquals('resolvedvalue', $baz->value());

		// add a deafult value to the given resolver
		$baz = $container->get('Baz', ['value' => [function($value) {
					return $value;
				}, ':value' => 'resolveddefaultvalue']]);
		$this->assertEquals(true, $baz instanceof Baz);
		$this->assertEquals('resolveddefaultvalue', $baz->value());


		// Set resolver consisting of object + method
		$container = $this->make();
		$factory = new Factory();
		$container->set('Baz', [$factory, 'create']);
		$baz = $container->get('Baz');
		$this->assertEquals(true, $baz instanceof Baz);
		$this->assertEquals('default', $baz->value());

		// provide an argument for the factory
		$baz = $container->get('Baz', [':value' => 'notdefault']);
		$this->assertEquals('notdefault', $baz->value());

		// Set resolver consisting of function name
		$container = $this->make();

		function create($val = 'default')
		{
			return new Baz($val);
		}


		$container->set('Baz', '~create');
		$baz = $container->get('Baz');
		$this->assertEquals(true, $baz instanceof Baz);
		$this->assertEquals('default', $baz->value());

		// provide an argument for the function
		$baz = $container->get('Baz', [':val' => 'notdefault']);
		$this->assertEquals('notdefault', $baz->value());
	}


	public function testAutowiring()
	{
		$container = $this->make();
		$foo = $container->get('Foo');
		$this->assertEquals(true, $foo instanceof Foo);
		$this->assertEquals('default', $foo->value());
		$this->assertEquals('default', $foo->barValue());
		$this->assertEquals('default', $foo->bazValue());

		$foo = $container->get('Foo', [
			'bar' => [':value' => 'notdefault'],
			'baz' => ['value' => function() {
					return 'alsonotdefault';
				}]
		]);
		$this->assertEquals('default', $foo->value());
		$this->assertEquals('notdefault', $foo->barValue());
		$this->assertEquals('alsonotdefault', $foo->bazValue());
	}


	public function testMultilevelResolution()
	{
		$container = $this->make();

		$container->set([
			'Alias1' => 'Foo',
			'Alias2' => ['Alias1', ':bar' => new Bar('notused')],
			'Alias3' => ['Alias2', ':baz' => new Baz('bazvalue')]
		]);

		$foo = $container->get('Alias3', ['bar' => function() {
				return new Bar('barvalue');
			}, ':value' => 'foovalue']);
		$this->assertEquals(true, $foo instanceof Foo);
		$this->assertEquals('foovalue', $foo->value());
		$this->assertEquals('barvalue', $foo->barValue());
		$this->assertEquals('bazvalue', $foo->bazValue());
	}


	public function testMultiset()
	{
		$container = $this->make();

		$container->set([
			'Foo' => [':value' => 'foovalue'],
			'Bar' => [':value' => 'barvalue'],
			'Baz' => [':value' => 'bazvalue'],
		]);

		$foo = $container->get('Foo');
		$this->assertEquals(true, $foo instanceof Foo);
		$this->assertEquals('foovalue', $foo->value());
		$this->assertEquals('barvalue', $foo->barValue());
		$this->assertEquals('bazvalue', $foo->bazValue());
	}


	public function testShare()
	{
		$container = $this->make();
		$container->share('Baz');

		$baz = $container->get('Baz', [':value' => 'bazvalue']);
		$this->assertEquals('bazvalue', $baz->value());
		$baz = $container->get('Baz');
		$this->assertEquals('bazvalue', $baz->value());
		$baz = $container->get('Baz', [':value' => 'anotherbazvalue']);
		$this->assertEquals('bazvalue', $baz->value());


		$container = $this->make();
		$container->share('Baz', new Baz('bazvalue'));
		$this->assertEquals('bazvalue', $baz->value());
		$baz = $container->get('Baz');
		$this->assertEquals('bazvalue', $baz->value());
		$baz = $container->get('Baz', [':value' => 'anotherbazvalue']);
		$this->assertEquals('bazvalue', $baz->value());



		$container = $this->make();
		$container->share([
			'Foo',
			'Bar',
			'Baz'
		]);

		$foo = $container->get('Foo', [
			':bar' => $container->get('Bar', [':value' => 'barvalue']),
			':value' => 'foovalue'
		]);

		$foo = $container->get('Foo', [':value' => 'notused']);
		$bar = $container->get('Bar', [':value' => 'notused']);
		$baz = $container->get('Baz', [':value' => 'notused']);

		$this->assertEquals('foovalue', $foo->value());
		$this->assertEquals('barvalue', $foo->barValue());
		$this->assertEquals('default', $foo->bazValue());
		$this->assertEquals('barvalue', $bar->value());
		$this->assertEquals('default', $baz->value());
	}


	public function testMake()
	{
		$container = $this->make();
		$container->share('Baz');

		$baz = $container->get('Baz', [':value' => 'bazvalue']);
		$baz = $container->make('Baz', [':value' => 'otherbazvalue']);
		$this->assertEquals('otherbazvalue', $baz->value());
	}


	public function testCall()
	{
		$container = $this->make();
		$callback = function($value = 'default') {
			return $value;
		};
		$this->assertEquals('default', $container->call($callback));
		$this->assertEquals('notdefault', $container->call($callback, [':value' => 'notdefault']));


		$container = $this->make();
		$container->set('context', [':value1' => 1]);
		$callback = function($value1 = 0, $value2 = 0) {
			return $value1 + $value2;
		};

		$this->assertEquals(0, $container->call($callback));
		$this->assertEquals(1, $container->call($callback, [], 'context'));
		$this->assertEquals(2, $container->call($callback, [':value2' => 1], 'context'));
		$this->assertEquals(3, $container->call($callback, [':value1' => 2, ':value2' => 1], 'context'));
		$this->assertEquals(3, $container->call($callback, [':value1' => 2, ':value2' => 1]));
	}


	public function testCircular()
	{
		$container = $this->make();
		$container->set('Foo', function() use ($container) {
			return $container->get('Bar');
		});
		$container->set('Bar', function() use ($container) {
			return $container->get('Foo');
		});
		try {
			$container->get('Foo');
		} catch (Exception $e) {
			return;
		}
		$this->fail('Circular dependency exception not thrown');
	}


	public function testInvalid()
	{
		$container = $this->make();
		$container->set('Foo', ['test', 'test']);
		try {
			$container->get('Foo');
		} catch (Exception $e) {
			return;
		}
		$this->fail('Invalid resolver exception not thrown');
	}


	public function testUnable()
	{
		$container = $this->make();
		$container->set('Foo', function($value) {
			return new Foo($value);
		});

		try {
			$container->get('Foo');
		} catch (Exception $e) {
			return;
		}
		$this->fail('Unable to resolve exception not thrown');
	}


	public function testCache()
	{
		$container = $this->make();
		$this->assertEquals(false, $container->changed());
		$this->assertEquals([], $container->reflected());

		$baz = $container->get('Baz');
		$this->assertEquals(true, $container->changed());
		$this->assertEquals([
			'Baz.__construct' => [
				'value' => [
					'hasDefault' => true,
					'default' => 'default',
					'optional' => true,
					'class' => null
				]
			]
		], $container->reflected());
	}
}