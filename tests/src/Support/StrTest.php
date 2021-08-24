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
        self::assertEquals('est', Str::after('test', 't'));

        // match last
        self::assertEquals('', Str::after('test1', '1'));

        // match empty string
        self::assertEquals('test', Str::after('test', ''));

        // no match
        self::assertEquals('', Str::after('test', 'test2'));

        // multi byte
        self::assertEquals('うえ', Str::after('ああいうえ', 'い'));
    }

    public function testAfterIndex()
    {
        self::assertEquals('', Str::afterIndex('abcde', 6));
        self::assertEquals('', Str::afterIndex('abcde', 5));
        self::assertEquals('e', Str::afterIndex('abcde', 4));
        self::assertEquals('a', Str::afterIndex('a', 0));
        self::assertEquals('a', Str::afterIndex('a', -0));
        self::assertEquals('e', Str::afterIndex('abcde', -1));
        self::assertEquals('abcde', Str::afterIndex('abcde', -5));
        self::assertEquals('bcde', Str::afterIndex('abcde', -4));
    }

    public function testAfterLast()
    {
        // match first (single occurrence)
        self::assertEquals('bc', Str::afterLast('abc', 'a'));

        // match first (multiple occurrence)
        self::assertEquals('1', Str::afterLast('test1', 't'));

        // match last
        self::assertEquals('', Str::afterLast('test1', '1'));

        // should match the last string
        self::assertEquals('Foo', Str::afterLast('----Foo','---'));

        // match empty string
        self::assertEquals('test', Str::afterLast('test', ''));

        // no match
        self::assertEquals('', Str::afterLast('test', 'test2'));

        // multi byte
        self::assertEquals('え', Str::afterLast('ああいういえ', 'い'));
    }

    public function testBefore()
    {
        // match first (single occurrence)
        self::assertEquals('a', Str::before('abc', 'b'));

        // match first (multiple occurrence)
        self::assertEquals('a', Str::before('abc-abc', 'b'));

        // match last
        self::assertEquals('test', Str::before('test1', '1'));

        // match empty string
        self::assertEquals('test', Str::before('test', ''));

        // no match
        self::assertEquals('test', Str::before('test', 'test2'));

        // multi byte
        self::assertEquals('ああ', Str::before('ああいういえ', 'い'));
    }

    public function testBeforeIndex()
    {
        self::assertEquals('abcde', Str::beforeIndex('abcde', 6));
        self::assertEquals('abcde', Str::beforeIndex('abcde', 5));
        self::assertEquals('abcd', Str::beforeIndex('abcde', 4));
        self::assertEquals('', Str::beforeIndex('a', 0));
        self::assertEquals('', Str::beforeIndex('a', -0));
        self::assertEquals('abcd', Str::beforeIndex('abcde', -1));
        self::assertEquals('', Str::beforeIndex('abcde', -5));
        self::assertEquals('a', Str::beforeIndex('abcde', -4));
    }

    public function testBeforeLast()
    {
        // match first (single occurrence)
        self::assertEquals('a', Str::beforeLast('abc', 'b'));

        // match first (multiple occurrence)
        self::assertEquals('abc-a', Str::beforeLast('abc-abc', 'b'));

        // match last
        self::assertEquals('test', Str::beforeLast('test1', '1'));

        // match empty string
        self::assertEquals('test', Str::beforeLast('test', ''));

        // no match
        self::assertEquals('test', Str::beforeLast('test', 'test2'));

        // multi byte
        self::assertEquals('ああいう', Str::beforeLast('ああいういえ', 'い'));
    }

    public function testCamelCase()
    {
        self::assertEquals('test', Str::camelCase('test'));
        self::assertEquals('test', Str::camelCase('Test'));
        self::assertEquals('testTest', Str::camelCase('test-test'));
        self::assertEquals('testTest', Str::camelCase('test_test'));
        self::assertEquals('testTest', Str::camelCase('test test'));
        self::assertEquals('testTestTest', Str::camelCase('test test test'));
        self::assertEquals('testTest', Str::camelCase(' test  test  '));
        self::assertEquals('testTestTest', Str::camelCase("--test_test-test__"));
    }

    public function testCapitalize()
    {
        self::assertEquals('Test', Str::capitalize('test'));
        self::assertEquals('Test abc', Str::capitalize('test abc'));
        self::assertEquals(' test abc', Str::capitalize(' test abc'));
        self::assertEquals('Àbc', Str::capitalize('àbc'));
        self::assertEquals('ゅ', Str::capitalize('ゅ'));
    }

    public function testContains()
    {
        self::assertTrue(Str::contains('abcde', 'ab'));
        self::assertFalse(Str::contains('abcde', 'ac'));
        self::assertTrue(Str::contains('abcde', ''));
        self::assertTrue(Str::contains('', ''));
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

    public function testContainsAny()
    {
        self::assertTrue(Str::containsAny('', ['']));
        self::assertTrue(Str::containsAny('abcde', ['']));

        self::assertTrue(Str::containsAny('abcde', ['a', 'z']));
        self::assertTrue(Str::containsAny('abcde', ['z', 'a']));
        self::assertTrue(Str::containsAny('abcde', ['a']));

        self::assertFalse(Str::containsAny('abcde', ['z']));
        self::assertFalse(Str::containsAny('abcde', ['y', 'z']));
    }

    public function testContainsAny_EmptyNeedles()
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Array cannot be empty.');
        Str::containsAny('abcde', []);
    }

    public function testContainsPattern()
    {
        self::assertTrue(Str::containsPattern('abc', '/b/'));
        self::assertTrue(Str::containsPattern('abc', '/ab/'));
        self::assertTrue(Str::containsPattern('abc', '/abc/'));
        self::assertTrue(Str::containsPattern('ABC', '/abc/i'));
        self::assertTrue(Str::containsPattern('aaaz', '/a{3}/'));
        self::assertTrue(Str::containsPattern('ABC1', '/[A-z0-9]+/'));
        self::assertTrue(Str::containsPattern('ABC1', '/[0-9]$/'));
        self::assertFalse(Str::containsPattern('AB1C', '/[0-9]$/'));
    }

    public function testDelete()
    {
        self::assertEquals('', Str::delete('aaa', 'a'));
        self::assertEquals('a  a', Str::delete('aaa aa a', 'aa'));
        self::assertEquals('', Str::delete('', ''));
        self::assertEquals('no match', Str::delete('no match', 'hctam on'));
    }

    public function testEndsWith()
    {
        self::assertTrue(Str::endsWith('abc', 'c'));
        self::assertFalse(Str::endsWith('abc', 'b'));
        self::assertTrue(Str::endsWith('abc', ['c']));
        self::assertTrue(Str::endsWith('abc', ['a', 'b', 'c']));
        self::assertFalse(Str::endsWith('abc', ['a', 'b']));
        self::assertTrue(Str::endsWith('aabbcc', 'cc'));
        self::assertTrue(Str::endsWith('aabbcc'.PHP_EOL, PHP_EOL));
        self::assertTrue(Str::endsWith('abc0', '0'));
        self::assertTrue(Str::endsWith('abcfalse', 'false'));
        self::assertTrue(Str::endsWith('a', ''));
        self::assertTrue(Str::endsWith('', ''));
        self::assertTrue(Str::endsWith('あいう', 'う'));
        self::assertFalse(Str::endsWith("あ\n", 'あ'));
    }

    public function testInsert()
    {
        self::assertEquals('xyzabc', Str::insert('abc', 'xyz', 0));
        self::assertEquals('axyzbc', Str::insert('abc', 'xyz', 1));
        self::assertEquals('abxyzc', Str::insert('abc', 'xyz', -1));
        self::assertEquals('abcxyz', Str::insert('abc', 'xyz', 3));
    }

    public function testKebabCase()
    {
        self::assertEquals('test', Str::kebabCase('test'));
        self::assertEquals('test', Str::kebabCase('Test'));
        self::assertEquals('ttt', Str::kebabCase('TTT'));
        self::assertEquals('tt-test', Str::kebabCase('TTTest'));
        self::assertEquals('test-test', Str::kebabCase('testTest'));
        self::assertEquals('test-t-test', Str::kebabCase('testTTest'));
        self::assertEquals('test-test', Str::kebabCase('test-test'));
        self::assertEquals('test-test', Str::kebabCase('test_test'));
        self::assertEquals('test-test', Str::kebabCase('test test'));
        self::assertEquals('test-test-test', Str::kebabCase('test test test'));
        self::assertEquals('-test-test-', Str::kebabCase(' test  test  '));
        self::assertEquals('-test-test-test-', Str::kebabCase("--test_test-test__"));
    }

}