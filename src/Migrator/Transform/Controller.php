<?php

namespace Migrator\Transform;

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Controller extends Transform
{
	public function transform()
	{
		$this->migrateControllers();
	}

	private function migrateControllers()
	{
		$finder = (new Finder)
			->files()
			->in($this->kohanaPath.'/application/classes/controller');

		/** @var \Symfony\Component\Finder\SplFileInfo $file */
		foreach ($finder as $file) {
			$this->migrateController($file);
		}
	}

	private function migrateController(SplFileInfo $file)
	{
		$content = $file->getContents();

		if ( ! preg_match('/class (.+?) /', $content, $match)) {
			return;
		}

		$initialClassName = $match[1];
		$className = str_replace('Controller_', '', $initialClassName) . 'Controller';
		$className = Str::studly($className);

		$content = strtr($content, [
			'<?php defined(\'SYSPATH\') or die(\'No direct script access.\');' => '<?php',
			'class '.$initialClassName => 'class '.$className,
			'View::factory' => 'View::make',
			'$this->request->param()' => 'Input::all()',
			'$this->request->query()' => 'Input::all()',
			'$this->request->post()' => 'Input::all()',
			'$this->request->param' => 'Input::get',
			'$this->request->query' => 'Input::get',
			'$this->request->post' => 'Input::get',
			'$this->request->is_ajax()' => 'Request::wantsJson()',
			'$this->json_response' => 'return Response::json',
			'throw new HTTP_Exception_403;' =>  'App::abort(403);',
			'throw new HTTP_Exception_404;' => 'App::abort(404);',
			'Text::begins_with' => 'Str::startsWith',
			'Text::ends_with' => 'Str::endsWith',

			// Model related
			'->loaded()' => '->exists',
			'->find()' => '->first()',
			'->find_all()' => '->all()',
			'->pk()' => '->getKey()',
			'->execute()' => '->get()',
			'order_by' => 'orderBy',
			'group_by' => 'groupBy',
			'DB::expr' => 'DB::raw',
			'as_array()' => 'toArray()',
			'assemble(' => 'map(',

			// eXolnet specific
			'$this->content = ' => 'return ',
		]);

		$content = preg_replace_callback('/ORM::factory\([\'"](\w+)[\'"]\)/i', function ($matches) {
			return '(new ' . $matches[1] . ')';
		}, $content);

		$targetPath = $this->laravelPath.'/app/controllers/'. $className .'.php';

		$this->write($targetPath, $content);
	}

}
