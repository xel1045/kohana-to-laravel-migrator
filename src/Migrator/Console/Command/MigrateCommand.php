<?php namespace Migrator\Console\Command;

use Migrator\Transform\Controller;
use Migrator\Transform\Model;
use Migrator\Transform\View;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command {
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
		$kohanaPath = rtrim($input->getArgument('kohana-path'), '/');
		$laravelPath = rtrim($input->getArgument('laravel-path'), '/');

		$transformers = [
			new Controller($kohanaPath, $laravelPath),
			new Model($kohanaPath, $laravelPath),
			new View($kohanaPath, $laravelPath),
		];

		foreach ($transformers as $transformer) {
			$transformer->transform();
		}
	}
}
