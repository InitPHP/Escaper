<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Tests;

use InitPHP\Escaper\Esc;
use InitPHP\Escaper\Exception\EncodingNotSupportedException;
use InitPHP\Escaper\Exception\InvalidContextException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class EscTest extends TestCase
{
    protected function setUp(): void
    {
        Esc::reset();
    }

    public function testHtmlContextIsDefault(): void
    {
        self::assertSame(
            '&lt;b&gt;hi&lt;/b&gt;',
            Esc::esc('<b>hi</b>')
        );
    }

    public function testEachContextDispatchesToTheCorrectEscaper(): void
    {
        self::assertSame('&lt;b&gt;', Esc::esc('<b>',  'html'));
        self::assertSame('&lt;',      Esc::esc('<',    'attr'));
        self::assertSame('\\x3C',     Esc::esc('<',    'js'));
        self::assertSame('\\3C ',     Esc::esc('<',    'css'));
        self::assertSame('%3C',       Esc::esc('<',    'url'));
    }

    public function testContextLookupIsCaseInsensitive(): void
    {
        self::assertSame('&lt;', Esc::esc('<', 'HTML'));
        self::assertSame('&lt;', Esc::esc('<', 'Attr'));
    }

    public function testRawContextReturnsInputUnchanged(): void
    {
        self::assertSame('<b>raw</b>', Esc::esc('<b>raw</b>', 'raw'));
    }

    public function testEmptyContextStringReturnsInputUnchanged(): void
    {
        self::assertSame('<b>raw</b>', Esc::esc('<b>raw</b>', ''));
    }

    public function testInvalidContextThrows(): void
    {
        $this->expectException(InvalidContextException::class);
        $this->expectExceptionMessage('Invalid escape context "xml".');

        Esc::esc('<b>x</b>', 'xml');
    }

    public function testArrayIsEscapedRecursively(): void
    {
        $input = [
            'a' => '<x>',
            'b' => ['c' => '<y>', 'd' => ['e' => '<z>']],
        ];

        self::assertSame(
            [
                'a' => '&lt;x&gt;',
                'b' => ['c' => '&lt;y&gt;', 'd' => ['e' => '&lt;z&gt;']],
            ],
            Esc::esc($input)
        );
    }

    public function testNonStringNonArrayValuesArePassedThrough(): void
    {
        self::assertSame(42,    Esc::esc(42));
        self::assertSame(3.14,  Esc::esc(3.14));
        self::assertTrue(Esc::esc(true));
        self::assertNull(Esc::esc(null));

        $object = new stdClass();
        self::assertSame($object, Esc::esc($object));
    }

    public function testNonStringValuesInsideArrayArePreserved(): void
    {
        $input = ['a' => '<x>', 'b' => 1, 'c' => null, 'd' => true];

        self::assertSame(
            ['a' => '&lt;x&gt;', 'b' => 1, 'c' => null, 'd' => true],
            Esc::esc($input)
        );
    }

    public function testEncodingIsPropagatedThroughRecursiveCalls(): void
    {
        // ISO-8859-1 byte for "é"
        $input = ['greeting' => "\xE9"];

        $output = Esc::esc($input, 'html', 'iso-8859-1');

        self::assertSame(['greeting' => "\xE9"], $output);
    }

    public function testInstancesAreMemoisedPerEncoding(): void
    {
        // Two consecutive default calls must reuse the same Escaper.
        Esc::reset();
        Esc::esc('a');
        Esc::esc('b');

        $reflection = new \ReflectionClass(Esc::class);
        $property   = $reflection->getProperty('instances');
        $property->setAccessible(true);

        /** @var array<string, object> $instances */
        $instances = $property->getValue();

        self::assertCount(1, $instances);
        self::assertArrayHasKey('utf-8', $instances);
    }

    public function testDifferentEncodingsAreCachedIndependently(): void
    {
        Esc::reset();
        Esc::esc('a', 'html');
        Esc::esc('a', 'html', 'iso-8859-1');
        Esc::esc('a', 'html', 'windows-1252');

        $reflection = new \ReflectionClass(Esc::class);
        $property   = $reflection->getProperty('instances');
        $property->setAccessible(true);

        /** @var array<string, object> $instances */
        $instances = $property->getValue();

        self::assertCount(3, $instances);
        self::assertArrayHasKey('utf-8',        $instances);
        self::assertArrayHasKey('iso-8859-1',   $instances);
        self::assertArrayHasKey('windows-1252', $instances);
    }

    public function testInvalidEncodingPropagatesAsEncodingException(): void
    {
        $this->expectException(EncodingNotSupportedException::class);

        Esc::esc('a', 'html', 'not-a-real-encoding');
    }
}
