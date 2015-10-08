<?php

namespace Migrator\Transform;

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Model extends Transform
{
	public function transform()
	{
		$this->migrateModels();
	}

	private function migrateModels()
	{
		$finder = (new Finder)
			->files()
			->in($this->kohanaPath.'/application/classes/model');

		/** @var \Symfony\Component\Finder\SplFileInfo $file */
		foreach ($finder as $file) {
			$this->migrateModel($file);
		}
	}

	private function migrateModel(SplFileInfo $file)
	{
		$content = $file->getContents();

		$isModelOrm = strpos($content, 'extends ORM') !== false;

		if ($isModelOrm) {
			$this->migrateModelOrm($file);
		} else {
			$this->migrateModelGeneric($file);
		}
	}

	private function migrateModelGeneric(SplFileInfo $file)
	{
		$targetPath = $this->laravelPath.'/app/models/'. $file->getFilename();

		//file_put_contents($targetPath, $file->getContents());
	}

	private function migrateModelOrm(SplFileInfo $file)
	{
		$content = $file->getContents();

		// Change class signature
		if ( ! preg_match('/\$_table_name = \'(.+?)\';/', $content, $match)) {
			$this->migrateModelGeneric($file);
			return;
		}

		$tableName = $match[1];
		$className = Str::studly($tableName);

		$content = preg_replace('/class\s(\S+)\sextends\s+ORM/i', 'class '.$className.' extends \\Eloquent', $content);

		$content = strtr($content, [
			'<?php defined(\'SYSPATH\') or die(\'No direct script access.\');' => '<?php',
			'$_table_name'  => '$table',
			'$_primary_key' => '$primaryKey',
			'$_safe_attributes' => '$fillable',
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
			'protected $_created_on' => 'const CREATED_AT',
			'protected $_updated_on' => 'const UPDATED_AT',
			'Model_' => '',
		]);

		$content = preg_replace_callback('/ORM::factory\([\'"](\w+)[\'"]\)/i', function ($matches) {
			return '(new ' . $matches[1] . ')';
		}, $content);

		// Update rules
		// Update relations

		$content = $this->convertRelations($content);
		$content = $this->convertJoins($content);

		// Save new model
		$targetPath = $this->laravelPath.'/app/models/'. $className .'.php';

		$this->write($targetPath, $content);
	}

	protected function convertRelations($content)
	{
		$relations = [
			'has_one' => 'hasOne',
			'has_many' => 'hasMany',
			'belongs_to' => 'belongsTo',
		];

		$regex = '/^\s*protected\s+\$_(?<relation>'.implode('|', array_keys($relations)).')\s*=\s*(?P<declaration>array\(.*?\);|\[.*?\];)/sm';
		$content = preg_replace_callback($regex, function($matches) use ($relations) {
			$relation = $matches['relation'];
			$relationFunction = $relations[$relation];
			$declaration = $matches['declaration'];
			eval('$_relations = '.$declaration);

			$newContent = [];
			foreach ($_relations as $relationName => $relationProperties) {
				$newContent[] = <<<EOF
	public function $relationName()
	{
		return \$this->{$relationFunction}('{$relationProperties['model']}');
	}
EOF;
			}
			return PHP_EOL.implode(PHP_EOL.PHP_EOL, $newContent);
		}, $content);

		return $content;
	}

	protected function convertJoins($content)
	{
		$regex = '/
			join\s*\([\'"]
			(?<join>[^\'"]+) # Match joined table
			[\'"]
			( # Check if there\'s a join type specified
			  \s*,\s*[\'"]
			  (?<join_type>[^\'"]+) # Match join type (left, right)
			  [\'"]
			)?
			\s*\)\s*->\s*on\s*\(
			(?<on>.+?) # Match on relation
			\)
		/xs';

		$content = preg_replace_callback($regex, function($matches) {
			$joinType = $matches['join_type'] ? $matches['join_type'].'Join': '';
			return $joinType.'(\''.$matches['join'].'\', '.$matches['on'].' )';
		}, $content);

		return $content;
	}
}
