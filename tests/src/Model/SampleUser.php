<?php declare(strict_types=1);

namespace Tests\Kirameki\Model;

use Kirameki\Model\Model;
use Kirameki\Model\ReflectionBuilder;

class SampleUser extends Model
{
    public static function define(ReflectionBuilder $builder): void
    {
        // inject using setTestReflection($reflection)
    }
}
