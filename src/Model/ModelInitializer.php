<?php

namespace Kirameki\Model;

use Kirameki\Core\Application;
use Kirameki\Core\InitializerInterface;
use Kirameki\Model\Casts\BoolCast;
use Kirameki\Model\Casts\DateTimeCast;
use Kirameki\Model\Casts\FloatCast;
use Kirameki\Model\Casts\IntCast;
use Kirameki\Model\Casts\JsonCast;
use Kirameki\Model\Casts\StringCast;
use Psr\Log\LoggerInterface;

class ModelInitializer implements InitializerInterface
{
    public function register(Application $app): void
    {
        $registrar = new CastRegistrar();
        $registrar->add('bool', static fn() => new BoolCast);
        $registrar->add('int', static fn() => new IntCast);
        $registrar->add('float', static fn() => new FloatCast);
        $registrar->add('string', static fn() => new StringCast);
        $registrar->add('datetime', static fn() => new DateTimeCast);
        $registrar->add('json', static fn() => new JsonCast);
        $app->singleton(CastRegistrar::class, $registrar);
    }
}
