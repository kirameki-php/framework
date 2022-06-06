<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Arr;
use Tests\Kirameki\TestCase;

class ArrTest extends TestCase
{
    public function test_of(): void
    {
        self::assertEquals([], Arr::of());

        self::assertEquals([1, 2, 3], Arr::of(1, 2, 3));

        self::assertEquals([1, 'a' => 2], Arr::of(1, a: 2));

        self::assertEquals(['a' => 1, 'b' => 2], Arr::of(a: 1, b: 2));
    }
}
