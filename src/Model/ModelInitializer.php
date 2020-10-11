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
        $registrar = new ModelManager();
        $registrar->setCast('bool', static fn() => new BoolCast);
        $registrar->setCast('int', static fn() => new IntCast);
        $registrar->setCast('float', static fn() => new FloatCast);
        $registrar->setCast('string', static fn() => new StringCast);
        $registrar->setCast('datetime', static fn() => new DateTimeCast);
        $registrar->setCast('json', static fn() => new JsonCast);
        $app->singleton(ModelManager::class, $registrar);
    }
}
