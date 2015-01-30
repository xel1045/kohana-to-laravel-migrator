<?php namespace Migrator\Console\Command;

use Migrator\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Exception\RuntimeException;

class MigrateCommand extends Command {
	protected $kohanaPath;

	protected $laravelPath;

	protected function configure()
	{
		$this
			->setName('migrate')
			->setDescription('Perform migration from Kohana to Laravel.')
			->setDefinition([
				new InputArgument('kohana-path', InputArgument::REQUIRED, 'Path to your root Kohana directory.'),
				new InputArgument('laravel-path', InputArgument::REQUIRED, 'Path to your root Laravel directory.'),
			]);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->kohanaPath = rtrim($input->getArgument('kohana-path'), '/');
		$this->laravelPath = rtrim($input->getArgument('laravel-path'), '/');

		$this->migrateModels();
		$this->migrateControllers();
		$this->migrateViews();
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

		// Change _table_name & _primary_key
		$content = strtr($content, [
			'$_table_name'  => '$table',
			'$_primary_key' => '$pk',
		]);

		// Update rules
		// Update relations

		// Save new model
		$targetPath = $this->laravelPath.'/app/models/'. $className .'.php';

		file_put_contents($targetPath, $content);
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
