<?php

namespace Migrator\Transform;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class View extends Transform
{
	public function transform()
	{
		$this->migrateViews();
	}

	private function migrateViews()
	{
		$finder = (new Finder)
			->files()
			->in($this->kohanaPath.'/application/views');

		/** @var \Symfony\Component\Finder\SplFileInfo $file */
		foreach ($finder as $file) {
			$this->migrateView($file);
		}
	}

	private function migrateView(SplFileInfo $file)
	{

	}
}
