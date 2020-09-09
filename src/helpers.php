<?php

use Kirameki\Application;
use Kirameki\Support\Config;
use Kirameki\Support\Env;
use Psr\Log\LoggerInterface;

function app(): Application
{
    return Application::instance();
}

function config(): Config
{
    return app()->config();
}

function env(string $name)
{
    return Env::get($name);
}

function logger(): LoggerInterface
{
    return app()->get(LoggerInterface::class);
}

function storage_path(string $relPath = null): string
{
    return app()->getBasePath('storage/'.$relPath);
}