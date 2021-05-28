<?php

use JetBrains\PhpStorm\Pure;
use Kirameki\Core\Application;
use Kirameki\Database\DatabaseManager;
use Kirameki\Event\EventManager;
use Kirameki\Logging\LogManager;
use Kirameki\Support\Collection;
use Kirameki\Core\Config;
use Kirameki\Core\Env;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

function app(): Application
{
    return Application::instance();
}

function collect(?iterable $items = null): Collection
{
    return new Collection($items);
}

function class_basename($class): string
{
    $class = is_object($class) ? get_class($class) : $class;
    return basename(str_replace('\\', '/', $class));
}

function config(): Config
{
    return app()->config();
}

function db(): DatabaseManager
{
    return app()->get(DatabaseManager::class);
}

function env(string $name): bool|string|null
{
    return Env::get($name);
}

function event(): EventManager
{
    return app()->get(EventManager::class);
}

function logger(): LogManager
{
    return app()->get(LogManager::class);
}

function storage_path(string $relPath = null): string
{
    return app()->getBasePath('storage/'.$relPath);
}

function uuid(): string
{
    return Uuid::uuid4()->toString();
}