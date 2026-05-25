<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Tests;

use InitPHP\Escaper\Escaper;
use PHPUnit\Framework\TestCase;

final class EscaperHtmlAttrTest extends TestCase
{
    private Escaper $escaper;

    protected function setUp(): void
    {
        $this->escaper = new Escaper();
    }

    public function testEmptyStringShortCircuits(): void
    {
        self::assertSame('', $this->escaper->escHtmlAttr(''));
    }

    public function testDigitsOnlyShortCircuits(): void
    {
        self::assertSame('12345', $this->escaper->escHtmlAttr('12345'));
    }

    public function testWhitelistCharactersPassThrough(): void
    {
        self::assertSame(
            'abc,XYZ.-_0123',
            $this->escaper->escHtmlAttr('abc,XYZ.-_0123')
        );
    }

    public function testQuotelessAttributeInjectionVector(): void
    {
        $input = 'faketitle onmouseover=alert(/InitPHP!/);';

        self::assertSame(
            'faketitle&#x20;onmouseover&#x3D;alert&#x28;&#x2F;InitPHP&#x21;&#x2F;&#x29;&#x3B;',
            $this->escaper->escHtmlAttr($input)
        );
    }

    public function testNamedEntitiesPreferredOverNumericForms(): void
    {
        self::assertSame('&quot;', $this->escaper->escHtmlAttr('"'));
        self::assertSame('&amp;', $this->escaper->escHtmlAttr('&'));
        self::assertSame('&lt;', $this->escaper->escHtmlAttr('<'));
        self::assertSame('&gt;', $this->escaper->escHtmlAttr('>'));
    }

    public function testControlCharactersBecomeReplacementCharacter(): void
    {
        // 0x00, 0x01, 0x1B all fall into the C0 range and must not survive.
        self::assertSame('&#xFFFD;', $this->escaper->escHtmlAttr("\x00"));
        self::assertSame('&#xFFFD;', $this->escaper->escHtmlAttr("\x01"));
        self::assertSame('&#xFFFD;', $this->escaper->escHtmlAttr("\x1B"));
    }

    public function testTabLineFeedAndCarriageReturnAreEscapedNotReplaced(): void
    {
        // Tab/LF/CR are explicitly exempted from the replacement rule.
        self::assertSame('&#x09;', $this->escaper->escHtmlAttr("\t"));
        self::assertSame('&#x0A;', $this->escaper->escHtmlAttr("\n"));
        self::assertSame('&#x0D;', $this->escaper->escHtmlAttr("\r"));
    }

    public function testC1ControlsBecomeReplacementCharacter(): void
    {
        // U+007F DEL (single-byte UTF-8).
        self::assertSame('&#xFFFD;', $this->escaper->escHtmlAttr("\x7F"));
        // U+0080 PADDING CHARACTER (multibyte UTF-8: 0xC2 0x80).
        self::assertSame('&#xFFFD;', $this->escaper->escHtmlAttr("\xC2\x80"));
        // U+009F APPLICATION PROGRAM COMMAND (multibyte UTF-8: 0xC2 0x9F).
        self::assertSame('&#xFFFD;', $this->escaper->escHtmlAttr("\xC2\x9F"));
    }

    public function testU00A0IsEscapedNotReplaced(): void
    {
        // U+00A0 NO-BREAK SPACE sits just outside the C1 range and must be
        // escaped as a normal character, not replaced.
        self::assertSame('&#xA0;', $this->escaper->escHtmlAttr("\xC2\xA0"));
    }

    public function testBmpMultibyteCharacterUsesFourDigitHex(): void
    {
        // U+015F LATIN SMALL LETTER S WITH CEDILLA (ş)
        self::assertSame('&#x015F;', $this->escaper->escHtmlAttr('ş'));
    }

    public function testSupplementaryPlaneCharacterEmitsFullHex(): void
    {
        // U+1F680 ROCKET — beyond the BMP.
        self::assertSame('&#x1F680;', $this->escaper->escHtmlAttr('🚀'));
    }
}
