<?php declare(strict_types=1);

namespace Kirameki\Testing\Concerns;

use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Connection;
use Kirameki\Testing\TestCase;

/**
 * @mixin TestCase
 */
trait UsesDatabases
{
    public function createTempConnection(string $driver, array $options = [])
    {
        $name = uniqid('test_');

        $adapter = match ($driver) {
            'mysql' => new MySqlAdapter($options + ['host' => 'mysql', 'database' => $name]),
        };
        $adapter->createDatabase();
        $this->runAfterTearDown(fn() => $adapter->dropDatabase());

        $connection = new Connection($name, $adapter, event());
        db()->addConnection($connection);

        return $connection;
    }
}
