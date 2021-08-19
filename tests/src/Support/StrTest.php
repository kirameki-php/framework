<?php declare(strict_types=1);

namespace Tests\Kirameki\Support;

use Kirameki\Support\Str;
use Tests\Kirameki\TestCase;

class StrTest extends TestCase
{
    public function testAfter()
    {
        // match first
        $result = Str::after('test', 't');
        self::assertEquals('est', $result);

        // match last
        $result = Str::after('test1', '1');
        self::assertEquals('', $result);

        // match empty string
        $result = Str::after('test', '');
        self::assertEquals('test', $result);

        // no match
        $result = Str::after('test', 'test2');
        self::assertEquals('', $result);

        // multi byte
        $result = Str::after('ああいうえ', 'い');
        self::assertEquals('うえ', $result);
    }

    public function testAfterLast()
    {
        // match first (single occurrence)
        $result = Str::afterLast('abc', 'a');
        self::assertEquals('bc', $result);

        // match first (multiple occurrence)
        $result = Str::afterLast('test1', 't');
        self::assertEquals('1', $result);

        // match last
        $result = Str::afterLast('test1', '1');
        self::assertEquals('', $result);

        // match empty string
        $result = Str::afterLast('test', '');
        self::assertEquals('test', $result);

        // no match
        $result = Str::afterLast('test', 'test2');
        self::assertEquals('', $result);

        // multi byte
        $result = Str::afterLast('ああいういえ', 'い');
        self::assertEquals('え', $result);
    }

    public function testBefore()
    {
        // match first (single occurrence)
        $result = Str::before('abc', 'b');
        self::assertEquals('a', $result);

        // match first (multiple occurrence)
        $result = Str::before('abc-abc', 'b');
        self::assertEquals('a', $result);

        // match last
        $result = Str::before('test1', '1');
        self::assertEquals('test', $result);

        // match empty string
        $result = Str::before('test', '');
        self::assertEquals('test', $result);

        // no match
        $result = Str::before('test', 'test2');
        self::assertEquals('test', $result);

        // multi byte
        $result = Str::before('ああいういえ', 'い');
        self::assertEquals('ああ', $result);
    }

    public function testBeforeLast()
    {
        // match first (single occurrence)
        $result = Str::beforeLast('abc', 'b');
        self::assertEquals('a', $result);

        // match first (multiple occurrence)
        $result = Str::beforeLast('abc-abc', 'b');
        self::assertEquals('abc-a', $result);

        // match last
        $result = Str::beforeLast('test1', '1');
        self::assertEquals('test', $result);

        // match empty string
        $result = Str::beforeLast('test', '');
        self::assertEquals('test', $result);

        // no match
        $result = Str::beforeLast('test', 'test2');
        self::assertEquals('test', $result);

        // multi byte
        $result = Str::beforeLast('ああいういえ', 'い');
        self::assertEquals('ああいう', $result);
    }
}