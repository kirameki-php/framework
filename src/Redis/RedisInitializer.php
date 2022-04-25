<?php declare(strict_types=1);

namespace Kirameki\Redis;

use Kirameki\Core\Application;
use Kirameki\Core\Initializer;
use Kirameki\Event\EventManager;
use Kirameki\Redis\Events\CommandExecuted;
use Kirameki\Support\Arr;
use Kirameki\Support\Str;
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
            return new RedisManager($app->config('redis'), $app->get(EventManager::class));
        });

        // Log Executed queries
        if ($app->inDebugMode()) {
            $app->get(EventManager::class)->listen(CommandExecuted::class, static function(CommandExecuted $event) {
                $name = $event->connection->getName();
                $command = $event->command;
                $args = Arr::map($event->args, static fn (mixed $v): string => Str::valueOf($v));
                $timeMs = $event->execTimeMs;
                $message = sprintf('[redis:%s] %s %s (%0.2f ms)', $name, $command, implode(' ', $args), $timeMs);
                logger()->debug($message, [
                    'name' => $name,
                    'command' => $command,
                    'args' => $event->args,
                    'result' => $event->result,
                    'timeMs' => $timeMs,
                ]);
            });
        }
    }
}
