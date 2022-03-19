<?php declare(strict_types=1);

namespace Kirameki\Core;

interface Initializer
{
    /**
     * @param Application $app
     * @return void
     */
    public function register(Application $app): void;
}
