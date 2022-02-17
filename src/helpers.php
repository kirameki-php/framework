<?php declare(strict_types=1);

use Kirameki\Core\Application;
use Kirameki\Database\DatabaseManager;
use Kirameki\Event\EventManager;
use Kirameki\Logging\LogManager;
use Kirameki\Support\Collection;
use Kirameki\Core\Config;
use Kirameki\Core\Env;

/**
 * @return Application
 */
function app(): Application
{
    return Application::instance();
}

/**
 * @template TKey of array-key
 * @template TValue
 * @param iterable<TKey, TValue>|null $items
 * @return Collection<TKey, TValue>
 */
function collect(?iterable $items = null): Collection
{
    return new Collection($items);
}

/**
 * @param string|object $class
 * @return non-empty-string
 */
function class_basename(string|object $class): string
{
    $class = is_object($class) ? get_class($class) : $class;
    return basename(str_replace('\\', '/', $class)); /** @phpstan-ignore-line */
}

/**
 * @return Config
 */
function config(): Config
{
    return app()->config();
}

/**
 * @return DatabaseManager
 */
function db(): DatabaseManager
{
    return app()->get(DatabaseManager::class);
}

/**
 * @param string $name
 * @return bool|string|null
 */
function env(string $name): bool|string|null
{
    return Env::get($name);
}

/**
 * @return EventManager
 */
function event(): EventManager
{
    return app()->get(EventManager::class);
}

/**
 * @return LogManager
 */
function logger(): LogManager
{
    return app()->get(LogManager::class);
}

/**
 * @param string|null $relPath
 * @return string
 */
function storage_path(string $relPath = null): string
{
    return app()->getBasePath('storage/'.$relPath);
}

/**
 * @template T
 * @param T $value
 * @param callable $callable
 * @return T
 */
function tap(mixed $value, callable $callable)
{
    $callable($value);
    return $value;
}
