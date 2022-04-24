<?php declare(strict_types=1);

namespace Kirameki\Redis;

use Kirameki\Core\Application;
use Kirameki\Core\Initializer;
use Kirameki\Event\EventManager;
use Kirameki\Redis\Events\CommandExecuted;
use Kirameki\Support\Str;
use function array_map;
use function implode;
use function sprintf;

class RedisInitializer implements Initializer
{
    /**
     * @param Application $app
     * @return void
     */
    public function register(Application $app): void
    {
        $app->singleton(RedisManager::class, function (Application $app) {
            return new RedisManager($app->get(EventManager::class));
        });

        // Log Executed queries
        if ($app->inDebugMode()) {
            $app->get(EventManager::class)->listen(CommandExecuted::class, static function(CommandExecuted $event) {
                $name = $event->connection->getName();
                $command = $event->command;
                $args = array_map(static fn (mixed $v) => Str::valueOf($v), $event->args);
                $result = $event->result;
                $timeMs = $event->execTimeMs;
                $message = sprintf('[redis:%s] %s %s (%0.2f ms)', $name, $command, implode(' ', $args), $timeMs);
                logger()->debug($message, [
                    'name' => $name,
                    'command' => $command,
                    'args' => $args,
                    'result' => $result,
                    'timeMs' => $timeMs,
                ]);
            });
        }
    }
}
