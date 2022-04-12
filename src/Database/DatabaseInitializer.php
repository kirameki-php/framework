<?php declare(strict_types=1);

namespace Kirameki\Database;

use Kirameki\Core\Application;
use Kirameki\Core\Initializer;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Event\EventManager;
use function sprintf;

class DatabaseInitializer implements Initializer
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
                $name = $event->connection->getName();
                $executedQuery = $event->getExecutedQuery();
                $elapsedMs = $event->elapsedMs;
                $message = sprintf('[db:%s] %s (%0.2f ms)', $name, $executedQuery, $elapsedMs);
                logger()->debug($message, [
                    'statement' => $event->statement,
                    'bindings' => $event->bindings,
                    'elapsedMs' => $elapsedMs,
                ]);
            });
        }
    }
}
