<?php

use Kirameki\Support\Env;

function env(string $name)
{
    return Env::get($name);
}
