<?php

namespace Kirameki\Database\Migration;

use DateTime;

class MigrationManager
{
    protected string $filePath;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->filePath = $path;
    }

    /**
     * @param DateTime|null $since
     */
    public function up(?DateTime $since = null)
    {
        foreach ($this->readMigrations($since) as $migration) {
            $migration->up();
            $migration->apply();
        }
    }

    /**
     * @param DateTime|null $since
     * @return Migration[]
     */
    public function inspectUp(?DateTime $since = null)
    {
        $ddls = [];
        foreach ($this->readMigrations($since) as $migration) {
            $migration->up();
            $ddls[] = $migration->toDdl();
        }
        return $ddls;
    }

    /**
     * @param DateTime|null $since
     * @return Migration[]
     */
    protected function readMigrations(?DateTime $since = null): array
    {
        $since = $since ? $since->format('Ymdhis') : '19700101000000';
        $migrations = [];
        foreach ($this->getMigrationFiles() as $file) {
            $datetime = strstr(class_basename($file), '_', true);
            if ($datetime === null || $datetime >= $since) {
                $className = rtrim(ltrim(strstr($file, '_'), '_'), '.php');
                require_once $file;
                $migrations[]= new $className($datetime);
            }
        }
        return $migrations;
    }

    /**
     * @return array
     */
    protected function getMigrationFiles(): array
    {
        return glob($this->filePath.'/*.php');
    }
}
