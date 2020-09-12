<?php

namespace Kirameki\Tests\Support;

use Kirameki\Support\Collection;
use Kirameki\Tests\TestCase;

class CollectionTest extends TestCase
{
    public function testAverage()
    {
        $arr = new Collection([1, 2]);
        $average = $arr->average();

        self::assertEquals(1.5, $average);
    }
}
