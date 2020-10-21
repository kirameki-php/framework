<?php declare(strict_types=1);

namespace Kirameki\Tests\Model;

use Kirameki\Database\Connection;
use Kirameki\Model\ModelManager;
use Kirameki\Model\Reflection;
use Kirameki\Model\ReflectionBuilder;
use Kirameki\Tests\TestCase;

class ModelTestCase extends TestCase
{
    public function makeReflectionBuilder(Reflection $reflection): ReflectionBuilder
    {
        $manager = $this->app->get(ModelManager::class);
        return new ReflectionBuilder($manager, $reflection);
    }
}
