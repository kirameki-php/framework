<?php declare(strict_types=1);

namespace Tests\Kirameki\Model;

use Kirameki\Model\ModelManager;
use Kirameki\Model\Reflection;
use Kirameki\Model\ReflectionBuilder;
use Tests\Kirameki\TestCase;

class ModelTestCase extends TestCase
{
    public function makeReflectionBuilder(Reflection $reflection): ReflectionBuilder
    {
        $manager = $this->app->get(ModelManager::class);
        return new ReflectionBuilder($manager, $reflection);
    }
}
