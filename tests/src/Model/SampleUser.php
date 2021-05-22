<?php

namespace Tests\Kirameki\Model;

use Kirameki\Model\Model;
use Kirameki\Model\Reflection;

class SampleUser extends Model
{
    public function define(Reflection $reflection): void
    {
        // inject using setTestReflection($reflection)
    }
}