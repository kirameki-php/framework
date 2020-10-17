<?php
namespace Kirameki\Database\Migration\Commands;

use Kirameki\Database\Migration\MigrationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
    protected static $defaultName = 'db:migrate';

    protected function configure()
    {
        $this->setDescription('migrate database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migrator = new MigrationManager(app()->getBasePath('database/migrations'));
        $migrator->up();

        return Command::SUCCESS;
    }
}
