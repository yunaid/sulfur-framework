<?php

namespace Sulfur\Cache;

class Framework
{

	/**
	 * Config
	 * @var array
	 */
	protected $config = [
		'path' => null
	];


	/**
	 * Construct
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		$this->config = array_merge($this->config, $config);
	}

	/**
	 * Reasd or write data
	 * @param string $key
	 * @param array $data
	 * @return array|void
	 */
	public function data($key, $data = null)
	{
		if($data === null) {
			if($this->config['path'] && file_exists($this->config['path'] . $key . '.php')) {
				return include($this->config['path'] . $key . '.php');
			} else {
				return [];
			}
		} else {
			if(! is_dir($this->config['path'])) {
				mkdir($this->config['path']);
				chmod($this->config['path'], 0777);
			}
			if($this->config['path'] && is_dir($this->config['path']) && is_writable($this->config['path'])) {
				file_put_contents($this->config['path'] . $key . '.php', '<?php return ' . var_export($data, true) . ';', LOCK_EX);
			}
		}
	}
}