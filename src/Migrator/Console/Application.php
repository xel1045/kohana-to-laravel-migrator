<?php namespace Migrator\Console;

use Migrator\Console\Command\MigrateCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication {
	const VERSION = '@package_version@';

	private static $logo = ' _   __      _                         _
| | / /     | |                       | |
| |/ /  ___ | |__   __ _ _ __   __ _  | |_ ___
|    \ / _ \| \'_ \ / _` | \'_ \ / _` | | __/ _ \
| |\  \ (_) | | | | (_| | | | | (_| | | || (_) |
\_| \_/\___/|_| |_|\__,_|_| |_|\__,_|  \__\___/


 _                               _  ___  ___                  _
| |                             | | |  \/  (_)               | |
| |     __ _ _ __ __ ___   _____| | | .  . |_  __ _ _ __ __ _| |_ ___  _ __
| |    / _` | \'__/ _` \ \ / / _ \ | | |\/| | |/ _` | \'__/ _` | __/ _ \| \'__|
| |___| (_| | | | (_| |\ V /  __/ | | |  | | | (_| | | | (_| | || (_) | |
\_____/\__,_|_|  \__,_| \_/ \___|_| \_|  |_/_|\__, |_|  \__,_|\__\___/|_|
                                               __/ |
                                              |___/
';

	public function __construct()
	{
		parent::__construct('Kohana to Laravel Migrator', self::VERSION);
	}

	public function getHelp()
	{
		return self::$logo . parent::getHelp();
	}

	protected function getDefaultCommands()
	{
		$commands = parent::getDefaultCommands();

		$commands[] = $this->add(new MigrateCommand());

		return $commands;
	}
}
