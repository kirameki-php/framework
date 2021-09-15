<?php declare(strict_types=1);

namespace Kirameki\Database\Migration\Commands;

use Kirameki\Database\Migration\MigrationManager;

class MigrateCommand extends Command
{
    protected static string $defaultName = 'db:migrate';

    protected function configure(): void
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
