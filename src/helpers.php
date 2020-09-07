<?php

use Kirameki\Application;
use Kirameki\Support\Config;
use Kirameki\Support\Env;

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
