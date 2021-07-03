<?php declare(strict_types=1);

namespace Kirameki\Database;

use Kirameki\Core\Application;
use Kirameki\Core\InitializerInterface;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Event\EventManager;
use function sprintf;

class DatabaseInitializer implements InitializerInterface
{
    /**
     * @param Application $app
     * @return void
     */
    public function register(Application $app): void
    {
        $app->singleton(DatabaseManager::class, function (Application $app) {
            return new DatabaseManager($app->get(EventManager::class));
        });

        // Log Executed queries
        if ($app->inDebugMode()) {
            $app->get(EventManager::class)->listen(QueryExecuted::class, static function(QueryExecuted $event) {
                $formatter = $event->connection->getQueryFormatter();
                $name = $event->connection->getName();
                $sql = $formatter->interpolate($event->statement, $event->bindings);
                $elapsedMs = $event->elapsedMs;
                $context = [
                    'statement' => $event->statement,
                    'bindings' => $event->bindings,
                    'elapsedMs' => $elapsedMs,
                ];
                $message = sprintf('[db:%s] %s (%0.2f ms)', $name, $sql, $elapsedMs);
                logger()->debug($message, $context);
            });
        }
    }
}
