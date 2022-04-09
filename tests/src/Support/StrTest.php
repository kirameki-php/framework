<?php declare(strict_types=1);

namespace Tests\Kirameki\Support;

use ErrorException;
use Kirameki\Support\Str;
use Tests\Kirameki\TestCase;
use Webmozart\Assert\InvalidArgumentException;

class StrTest extends TestCase
{
    public function testAfter(): void
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

        // grapheme
        self::assertEquals('def', Str::after('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿def', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
    }

    public function testAfterIndex(): void
    {
        self::assertEquals('', Str::afterIndex('abcde', 6));
        self::assertEquals('', Str::afterIndex('abcde', 5));
        self::assertEquals('e', Str::afterIndex('abcde', 4));
        self::assertEquals('a', Str::afterIndex('a', 0));
        self::assertEquals('a', Str::afterIndex('a', -0));
        self::assertEquals('e', Str::afterIndex('abcde', -1));
        self::assertEquals('abcde', Str::afterIndex('abcde', -5));
        self::assertEquals('bcde', Str::afterIndex('abcde', -4));

        // grapheme
        self::assertEquals('def', Str::afterIndex('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿def', 4));
    }

    public function testAfterLast(): void
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

        // grapheme
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿f', Str::afterLast('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', 'e'));
    }

    public function testBefore(): void
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

        // grapheme
        self::assertEquals('abc', Str::before('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
        self::assertEquals('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::before('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', 'e'));
    }

    public function testBeforeIndex(): void
    {
        self::assertEquals('abcde', Str::beforeIndex('abcde', 6));
        self::assertEquals('abcde', Str::beforeIndex('abcde', 5));
        self::assertEquals('abcd', Str::beforeIndex('abcde', 4));
        self::assertEquals('', Str::beforeIndex('a', 0));
        self::assertEquals('', Str::beforeIndex('a', -0));
        self::assertEquals('abcd', Str::beforeIndex('abcde', -1));
        self::assertEquals('', Str::beforeIndex('abcde', -5));
        self::assertEquals('a', Str::beforeIndex('abcde', -4));

        // grapheme
        self::assertEquals('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::beforeIndex('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', 4));
    }

    public function testBeforeLast(): void
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

        // grapheme
        self::assertEquals('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e', Str::beforeLast('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
    }

    public function testCamelCase(): void
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

    public function testCapitalize(): void
    {
        self::assertEquals('Test', Str::capitalize('test'));
        self::assertEquals('Test abc', Str::capitalize('test abc'));
        self::assertEquals(' test abc', Str::capitalize(' test abc'));
        self::assertEquals('Àbc', Str::capitalize('àbc'));
        self::assertEquals('ゅ', Str::capitalize('ゅ'));
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::capitalize('🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
    }

    public function testContains(): void
    {
        self::assertTrue(Str::contains('abcde', 'ab'));
        self::assertFalse(Str::contains('abcde', 'ac'));
        self::assertTrue(Str::contains('abcde', ''));
        self::assertTrue(Str::contains('', ''));
    }

    public function testContainsAll(): void
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

    public function testContainsAll_EmptyNeedles(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an array to contain at least 1 elements. Got: 0');
        Str::containsAll('abcde', []);
    }

    public function testContainsAny(): void
    {
        self::assertTrue(Str::containsAny('', ['']));
        self::assertTrue(Str::containsAny('abcde', ['']));

        self::assertTrue(Str::containsAny('abcde', ['a', 'z']));
        self::assertTrue(Str::containsAny('abcde', ['z', 'a']));
        self::assertTrue(Str::containsAny('abcde', ['a']));

        self::assertFalse(Str::containsAny('abcde', ['z']));
        self::assertFalse(Str::containsAny('abcde', ['y', 'z']));
    }

    public function testContainsAny_EmptyNeedles(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an array to contain at least 1 elements. Got: 0');
        Str::containsAny('abcde', []);
    }

    public function testContainsPattern(): void
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

    public function testDelete(): void
    {
        self::assertEquals('', Str::delete('aaa', 'a'));
        self::assertEquals('a  a', Str::delete('aaa aa a', 'aa'));
        self::assertEquals('', Str::delete('', ''));
        self::assertEquals('no match', Str::delete('no match', 'hctam on'));
    }

    public function testEndsWith(): void
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

    public function testInsert(): void
    {
        self::assertEquals('xyzabc', Str::insert('abc', 'xyz', 0));
        self::assertEquals('axyzbc', Str::insert('abc', 'xyz', 1));
        self::assertEquals('abxyzc', Str::insert('abc', 'xyz', -1));
        self::assertEquals('abcxyz', Str::insert('abc', 'xyz', 3));
        self::assertEquals('あxyzい', Str::insert('あい', 'xyz', 1));
        self::assertEquals('あxyzい', Str::insert('あい', 'xyz', -1));
    }

    public function testKebabCase(): void
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

    public function testLength(): void
    {
        self::assertEquals(0, Str::length(''));
        self::assertEquals(4, Str::length('Test'));
        self::assertEquals(9, Str::length(' T e s t '));
        self::assertEquals(2, Str::length('あい'));
        self::assertEquals(4, Str::length('あいzう'));
    }

    public function testMatch(): void
    {
        self::assertEquals(['a'], Str::match('abcabc', '/a/'));
        self::assertEquals(['abc', 'p1' => 'a', 'a'], Str::match('abcabc', '/(?<p1>a)bc/'));
        self::assertEquals([], Str::match('abcabc', '/bcd/'));
        self::assertEquals(['cd'], Str::match('abcdxabc', '/c[^x]*/'));
        self::assertEquals([], Str::match('abcabcx', '/^abcx/'));
        self::assertEquals(['cx'], Str::match('abcabcx', '/cx$/'));
    }

    public function testMatch_withoutSlashes(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('preg_match(): Delimiter must not be alphanumeric or backslash');
        Str::match('abcabc', 'a');
    }

    public function testMatchAll(): void
    {
        self::assertEquals([['a', 'a']], Str::matchAll('abcabc', '/a/'));
        self::assertEquals([['abc', 'abc'], 'p1' => ['a', 'a'], ['a', 'a']], Str::matchAll('abcabc', '/(?<p1>a)bc/'));
        self::assertEquals([[]], Str::matchAll('abcabc', '/bcd/'));
        self::assertEquals([['cd', 'c']], Str::matchAll('abcdxabc', '/c[^x]*/'));
        self::assertEquals([[]], Str::matchAll('abcabcx', '/^abcx/'));
        self::assertEquals([['cx']], Str::matchAll('abcabcx', '/cx$/'));
    }

    public function testMatchAll_withoutSlashes(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('preg_match_all(): Delimiter must not be alphanumeric or backslash');
        Str::matchAll('abcabc', 'a');
    }

    public function testNotContains(): void
    {
        self::assertTrue(Str::notContains('abcde', 'ac'));
        self::assertFalse(Str::notContains('abcde', 'ab'));
        self::assertFalse(Str::notContains('a', ''));
        self::assertTrue(Str::notContains('', 'a'));
    }

    public function testOf(): void
    {
        self::assertEquals('test', Str::of('test')->toString());
        self::assertEquals('', Str::of()->toString());
    }

    public function testPadBoth(): void
    {
        self::assertEquals('a', Str::padBoth('a', -1, '_'));
        self::assertEquals('a', Str::padBoth('a', 0, '_'));
        self::assertEquals('a_', Str::padBoth('a', 2, '_'));
        self::assertEquals('__', Str::padBoth('_', 2, '_'));
        self::assertEquals('_a_', Str::padBoth('a', 3, '_'));
        self::assertEquals('__a__', Str::padBoth('a', 5, '_'));
        self::assertEquals('__a___', Str::padBoth('a', 6, '_'));
        self::assertEquals('12hello123', Str::padBoth('hello', 10, '123'));
        self::assertEquals('いあい', Str::padBoth('あ', 3, 'い'));
    }

    public function testPadLeft(): void
    {
        self::assertEquals('a', Str::padLeft('a', -1, '_'));
        self::assertEquals('a', Str::padLeft('a', 0, '_'));
        self::assertEquals('_a', Str::padLeft('a', 2, '_'));
        self::assertEquals('__', Str::padLeft('_', 2, '_'));
    }

    public function testPadRight(): void
    {
        self::assertEquals('a', Str::padRight('a', -1, '_'));
        self::assertEquals('a', Str::padRight('a', 0, '_'));
        self::assertEquals('a_', Str::padRight('a', 2, '_'));
        self::assertEquals('__', Str::padRight('_', 2, '_'));
    }

    public function testPascalCase(): void
    {
        self::assertEquals('A', Str::pascalCase('a'));
        self::assertEquals('TestMe', Str::pascalCase('test_me'));
        self::assertEquals('TestMe', Str::pascalCase('test-me'));
        self::assertEquals('TestMe', Str::pascalCase('test me'));
        self::assertEquals('TestMe', Str::pascalCase('testMe'));
        self::assertEquals('TestMe', Str::pascalCase('TestMe'));
        self::assertEquals('TestMe', Str::pascalCase(' test_me '));
        self::assertEquals('TestMeNow!', Str::pascalCase('test_me now-!'));
    }

    public function testPosition(): void
    {
        self::assertEquals(0, Str::position('a', 'a'));
        self::assertEquals(1, Str::position('ab', 'b'));
    }

    public function testRepeat(): void
    {
        self::assertEquals('aaa', Str::repeat('a', 3));
        self::assertEquals('', Str::repeat('a', 0));
    }

    public function testRepeatNegativeTimes(): void
    {
        $this->expectError();
        $this->expectErrorMessage('str_repeat(): Argument #2 ($times) must be greater than or equal to 0');
        /** @noinspection PhpExpressionResultUnusedInspection */
        Str::repeat('a', -1);
    }

    public function testReplace(): void
    {
        self::assertEquals('', Str::replace('', '', ''));
        self::assertEquals('b', Str::replace('b', '', 'a'));
        self::assertEquals('aa', Str::replace('bb', 'b', 'a'));
        self::assertEquals('', Str::replace('b', 'b', ''));
        self::assertEquals('あえいえう', Str::replace('あ-い-う', '-', 'え'));
        self::assertEquals('__🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::replace('🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🏴󠁧󠁢󠁳󠁣󠁴󠁿a', '_'));
    }

    public function testReplaceFirst(): void
    {
        self::assertEquals('', Str::replaceFirst('', '', ''));
        self::assertEquals('bb', Str::replaceFirst('bb', '', 'a'));
        self::assertEquals('abb', Str::replaceFirst('bbb', 'b', 'a'));
        self::assertEquals('b', Str::replaceFirst('bb', 'b', ''));
        self::assertEquals('あえい-う', Str::replaceFirst('あ-い-う', '-', 'え'));
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿 a', Str::replaceFirst('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦', 'a'));
        self::assertEquals('_🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::replaceFirst('🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🏴󠁧󠁢󠁳󠁣󠁴󠁿a', '_'));
    }

    public function testReplaceLast(): void
    {
        self::assertEquals('', Str::replaceLast('', '', ''));
        self::assertEquals('bb', Str::replaceLast('bb', '', 'a'));
        self::assertEquals('bba', Str::replaceLast('bbb', 'b', 'a'));
        self::assertEquals('b', Str::replaceLast('bb', 'b', ''));
        self::assertEquals('あ-いえう', Str::replaceLast('あ-い-う', '-', 'え'));
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿 a', Str::replaceLast('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦', 'a'));
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿a_🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::replaceLast('🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🏴󠁧󠁢󠁳󠁣󠁴󠁿a', '_'));
    }

    public function testReverse(): void
    {
        self::assertEquals('', Str::reverse(''));
        self::assertEquals('ba', Str::reverse('ab'));
        self::assertEquals('ういあ', Str::reverse('あいう'));
        self::assertEquals('cbあ🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::reverse('🏴󠁧󠁢󠁳󠁣󠁴󠁿あbc'));
    }
}