<?php declare(strict_types=1);

namespace Tests\Kirameki\Support;

use Kirameki\Support\Str;
use RuntimeException;
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

        // should match the last string
        $result = Str::afterLast('----Foo','---');
        self::assertEquals('Foo', $result);

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

    public function testCamelCase()
    {
        $result = Str::camelCase('test');
        self::assertEquals('test', $result);

        $result = Str::camelCase('Test');
        self::assertEquals('test', $result);

        $result = Str::camelCase('test-test');
        self::assertEquals('testTest', $result);

        $result = Str::camelCase('test_test');
        self::assertEquals('testTest', $result);

        $result = Str::camelCase('test test');
        self::assertEquals('testTest', $result);

        $result = Str::camelCase('test test test');
        self::assertEquals('testTestTest', $result);

        $result = Str::camelCase(' test  test  ');
        self::assertEquals('testTest', $result);

        $result = Str::camelCase("--test_test-test__");
        self::assertEquals('testTestTest', $result);
    }

    public function testCapitalize()
    {
        $result = Str::capitalize('test');
        self::assertEquals('Test', $result);

        $result = Str::capitalize('test abc');
        self::assertEquals('Test abc', $result);

        $result = Str::capitalize(' test abc');
        self::assertEquals(' test abc', $result);

        $result = Str::capitalize('àbc');
        self::assertEquals('Àbc', $result);

        // do not uppercase japanese
        $result = Str::capitalize('ゅ');
        self::assertEquals('ゅ', $result);
    }

    public function testContains()
    {
        self::assertTrue(Str::contains('abcde', 'ab'));
        self::assertFalse(Str::contains('abcde', 'ac'));

        self::assertTrue(Str::contains('', ''));
        self::assertTrue(Str::contains('', ['']));
        self::assertTrue(Str::contains('abcde', ''));
        self::assertTrue(Str::contains('abcde', ['']));

        self::assertTrue(Str::contains('abcde', ['a', 'z']));
        self::assertTrue(Str::contains('abcde', ['z', 'a']));
        self::assertTrue(Str::contains('abcde', ['a']));

        self::assertFalse(Str::contains('abcde', ['z']));
        self::assertFalse(Str::contains('abcde', ['y', 'z']));
    }

    public function testContains_EmptyNeedles()
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Array cannot be empty.');
        Str::contains('abcde', []);
    }

    public function testContainsAll()
    {
        self::assertTrue(Str::containsAll('', ['']));
        self::assertTrue(Str::containsAll('abcde', ['']));

        self::assertFalse(Str::containsAll('abcde', ['a', 'z']));
        self::assertFalse(Str::containsAll('abcde', ['z', 'a']));
        self::assertTrue(Str::containsAll('abcde', ['a']));
        self::assertTrue(Str::containsAll('abcde', ['a', 'b']));
        self::assertTrue(Str::containsAll('abcde', ['c', 'b']));

        self::assertFalse(Str::containsAll('abcde', ['z']));
        self::assertFalse(Str::containsAll('abcde', ['y', 'z']));
    }

    public function testContainsAll_EmptyNeedles()
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Array cannot be empty.');
        Str::containsAll('abcde', []);
    }

    public function testDelete()
    {
        $result = Str::delete('aaa', 'a');
        self::assertEquals('', $result);

        $result = Str::delete('aaa aa a', 'aa');
        self::assertEquals('a  a', $result);

        $result = Str::delete('', '');
        self::assertEquals('', $result);

        $result = Str::delete('no match', 'hctam on');
        self::assertEquals('no match', $result);
    }
}