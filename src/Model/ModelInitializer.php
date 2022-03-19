<?php declare(strict_types=1);

namespace Kirameki\Model;

use Kirameki\Core\Application;
use Kirameki\Core\Initializer;
use Kirameki\Database\DatabaseManager;
use Kirameki\Model\Casts\BoolCast;
use Kirameki\Model\Casts\DateTimeCast;
use Kirameki\Model\Casts\EnumCast;
use Kirameki\Model\Casts\FloatCast;
use Kirameki\Model\Casts\IntCast;
use Kirameki\Model\Casts\CollectionCast;
use Kirameki\Model\Casts\StringCast;

class ModelInitializer implements Initializer
{
    public function register(Application $app): void
    {
        $app->singleton(ModelManager::class, function(Application $app) {
            $databaseManager = $app->get(DatabaseManager::class);
            $registrar = new ModelManager($databaseManager);
            $registrar->setCast('bool', static fn() => new BoolCast());
            $registrar->setCast('int', static fn() => new IntCast());
            $registrar->setCast('float', static fn() => new FloatCast());
            $registrar->setCast('string', static fn() => new StringCast());
            $registrar->setCast('datetime', static fn() => new DateTimeCast());
            $registrar->setCast('collection', static fn() => new CollectionCast());
            $registrar->setCast('{enum}', static fn(string $name) => new EnumCast($name)); /** @phpstan-ignore-line */
            return $registrar;
        });
    }
}
