<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Tests;

use InitPHP\Escaper\Escaper;
use InitPHP\Escaper\Exception\EncodingConversionException;
use InitPHP\Escaper\Exception\EncodingNotSupportedException;
use InitPHP\Escaper\Exception\EscaperException;
use InitPHP\Escaper\Exception\InvalidUtf8Exception;
use PHPUnit\Framework\TestCase;

final class EscaperEncodingTest extends TestCase
{
    public function testDefaultsToUtf8(): void
    {
        self::assertSame('utf-8', (new Escaper())->getEncoding());
    }

    public function testNullEncodingResolvesToUtf8(): void
    {
        self::assertSame('utf-8', (new Escaper(null))->getEncoding());
    }

    public function testEmptyStringEncodingResolvesToUtf8(): void
    {
        self::assertSame('utf-8', (new Escaper(''))->getEncoding());
    }

    public function testEncodingLookupIsCaseInsensitive(): void
    {
        self::assertSame('utf-8', (new Escaper('UTF-8'))->getEncoding());
        self::assertSame('windows-1252', (new Escaper('Windows-1252'))->getEncoding());
        self::assertSame('iso-8859-1', (new Escaper('ISO-8859-1'))->getEncoding());
    }

    public function testUnsupportedEncodingThrows(): void
    {
        $this->expectException(EncodingNotSupportedException::class);
        $this->expectExceptionMessage('Encoding "utf-16" is not supported.');

        new Escaper('utf-16');
    }

    public function testEncodingExceptionIsAnEscaperException(): void
    {
        try {
            new Escaper('not-a-real-encoding');
            self::fail('Expected EncodingNotSupportedException');
        } catch (EscaperException $e) {
            self::assertInstanceOf(EncodingNotSupportedException::class, $e);
        }
    }

    public function testNonUtf8InputIsConvertedThenEscaped(): void
    {
        $escaper = new Escaper('iso-8859-1');

        // ISO-8859-1 byte 0xE9 == "é". When fed in as a single byte the
        // escaper must first decode it to UTF-8, then re-encode the output
        // back to ISO-8859-1.
        $output = $escaper->escHtml("\xE9");

        // htmlspecialchars receives 'é' in UTF-8 and leaves it alone, but
        // returns it encoded back to ISO-8859-1 → 0xE9.
        self::assertSame("\xE9", $output);
    }

    public function testInvalidUtf8InAttributeContextThrows(): void
    {
        $this->expectException(InvalidUtf8Exception::class);

        // 0xC3 0x28 is a broken 2-byte sequence.
        (new Escaper())->escHtmlAttr("\xC3\x28");
    }

    public function testWindows1252RoundTripThroughAttributeContext(): void
    {
        $escaper = new Escaper('windows-1252');

        // 0xE9 == "é" in windows-1252. It is outside the attribute whitelist
        // so the matcher must produce a numeric entity. The output reaches
        // the caller after a UTF-8 → windows-1252 conversion back.
        self::assertSame('&#xE9;', $escaper->escHtmlAttr("\xE9"));
    }

    public function testWindows1252RoundTripThroughJsContext(): void
    {
        $escaper = new Escaper('windows-1252');

        // 0xE9 == "é". In the JS context the matcher emits é.
        self::assertSame('\\u00E9', $escaper->escJs("\xE9"));
    }

    public function testWindows1252RoundTripThroughCssContext(): void
    {
        $escaper = new Escaper('windows-1252');

        // 0xE9 == "é". In the CSS context the matcher emits "\E9 ".
        self::assertSame('\\E9 ', $escaper->escCss("\xE9"));
    }

    public function testForwardConversionFailureRaisesException(): void
    {
        $this->expectException(EncodingConversionException::class);
        $this->expectExceptionMessage('Failed to convert string from "windows-1252" to "UTF-8".');

        // 0x81 is an undefined byte in windows-1252 — iconv returns false.
        (new Escaper('windows-1252'))->escHtmlAttr("\x81");
    }
}
