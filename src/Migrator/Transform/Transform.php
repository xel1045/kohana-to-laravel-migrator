<?php

namespace Migrator\Transform;

abstract class Transform
{
	/**
	 * @var string
	 */
	protected $kohanaPath;
	/**
	 * @var string
	 */
	protected $laravelPath;

	/**
	 * @param string $source
	 * @param $destination
	 */
	public function __construct($kohanaPath, $laravelPath)
	{
		$this->kohanaPath = $kohanaPath;
		$this->laravelPath = $laravelPath;
	}

	public abstract function transform();

	protected function write($filename, $content)
	{
		$directory = dirname($filename);
		if ( ! file_exists($directory)) {
			mkdir($directory, 0777, true);
		}

		file_put_contents($filename, $content);
	}
}
