<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Tests;

use InitPHP\Escaper\Escaper;
use PHPUnit\Framework\TestCase;

final class EscaperJsTest extends TestCase
{
    private Escaper $escaper;

    protected function setUp(): void
    {
        $this->escaper = new Escaper();
    }

    public function testEmptyStringShortCircuits(): void
    {
        self::assertSame('', $this->escaper->escJs(''));
    }

    public function testDigitsOnlyShortCircuits(): void
    {
        self::assertSame('98765', $this->escaper->escJs('98765'));
    }

    public function testWhitelistCharactersPassThrough(): void
    {
        self::assertSame('abc,XYZ._0', $this->escaper->escJs('abc,XYZ._0'));
    }

    public function testEntityBasedInjectionVectorIsEscaped(): void
    {
        $input = 'bar&quot;; alert(&quot;Hello!&quot;); var xss=&quot;true';

        self::assertSame(
            'bar\\x26quot\\x3B\\x3B\\x20alert\\x28\\x26quot\\x3BHello\\x21\\x26quot\\x3B\\x29\\x3B\\x20var\\x20xss\\x3D\\x26quot\\x3Btrue',
            $this->escaper->escJs($input)
        );
    }

    public function testSingleByteSpecialCharsBecomeHexEscapes(): void
    {
        self::assertSame('\\x20', $this->escaper->escJs(' '));
        self::assertSame('\\x22', $this->escaper->escJs('"'));
        self::assertSame('\\x2F', $this->escaper->escJs('/'));
        self::assertSame('\\x3C', $this->escaper->escJs('<'));
    }

    public function testBmpMultibyteCharacterBecomesUnicodeEscape(): void
    {
        // U+015F LATIN SMALL LETTER S WITH CEDILLA (ş)
        self::assertSame('\\u015F', $this->escaper->escJs('ş'));
    }

    public function testSupplementaryPlaneCharacterBecomesSurrogatePair(): void
    {
        // U+1F680 → high surrogate D83D + low surrogate DE80
        self::assertSame('\\uD83D\\uDE80', $this->escaper->escJs('🚀'));
    }
}
