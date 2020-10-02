<?php

namespace Kirameki\Core;

interface InitializerInterface
{
    public function register(Application $app): void;
}
