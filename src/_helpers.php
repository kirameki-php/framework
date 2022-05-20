<?php declare(strict_types=1);

use Kirameki\Core\Application;
use Kirameki\Database\DatabaseManager;
use Kirameki\Event\EventManager;
use Kirameki\Logging\LogManager;
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
function storage_path(?string $relPath = null): string
{
    return app()->getBasePath('storage/'.$relPath);
}

