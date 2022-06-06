<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use ErrorException;
use InvalidArgumentException;
use Kirameki\Collections\Arr;
use Kirameki\Support\Collection;
use RuntimeException;
use Tests\Kirameki\TestCase;
use TypeError;
use function collect;

class ArrTest extends TestCase
{
    public function test_of(): void
    {
        $array = Arr::of(1, 2, 3);
        self::assertEquals([1, 2, 3], $array);

        $array = Arr::of(1, a: 2, b: 3);
        self::assertEquals([1, 'a' => 2, 'b' => 3], $array);

        $array = Arr::of(a: 1, b: 2, c: 3);
        self::assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $array);
    }
}
