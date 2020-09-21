<?php

use Kirameki\Application;
use Kirameki\Support\Collection;
use Kirameki\Core\Config;
use Kirameki\Core\Env;
use Psr\Log\LoggerInterface;

function app(): Application
{
    return Application::instance();
}

function collect(?iterable $items = null)
{
    return new Collection($items);
}

function config(): Config
{
    return app()->config();
}

function env(string $name)
{
    return Env::get($name);
}

function class_basename($class): string
{
    $class = is_object($class) ? get_class($class) : $class;
    return basename(str_replace('\\', '/', $class));
}

function logger(): LoggerInterface
{
    return app()->get(LoggerInterface::class);
}

function storage_path(string $relPath = null): string
{
    return app()->getBasePath('storage/'.$relPath);
}
