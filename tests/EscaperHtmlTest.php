<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Tests;

use InitPHP\Escaper\Escaper;
use PHPUnit\Framework\TestCase;

final class EscaperHtmlTest extends TestCase
{
    private Escaper $escaper;

    protected function setUp(): void
    {
        $this->escaper = new Escaper();
    }

    public function testEscapesAngleBracketsAndQuotes(): void
    {
        $input  = '<script>alert("xss")</script>';
        $output = $this->escaper->escHtml($input);

        self::assertSame(
            '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            $output
        );
    }

    public function testEscapesSingleQuoteWithEntQuotes(): void
    {
        self::assertSame('&#039;', $this->escaper->escHtml("'"));
    }

    public function testEscapesAmpersand(): void
    {
        self::assertSame('Tom &amp; Jerry', $this->escaper->escHtml('Tom & Jerry'));
    }

    public function testEmptyStringReturnsEmptyString(): void
    {
        self::assertSame('', $this->escaper->escHtml(''));
    }

    public function testPlainAsciiPassesThroughUnchanged(): void
    {
        self::assertSame('Hello, world!', $this->escaper->escHtml('Hello, world!'));
    }

    public function testMultibyteCharactersPassThroughInUtf8(): void
    {
        // htmlspecialchars only touches &, <, >, ", ' — multibyte stays.
        self::assertSame('Merhaba dünya — şŞıİğĞ', $this->escaper->escHtml('Merhaba dünya — şŞıİğĞ'));
    }

    public function testInvalidByteSequenceIsReplacedNotDropped(): void
    {
        // ENT_SUBSTITUTE replaces malformed UTF-8 with U+FFFD instead of
        // returning an empty string (the unsafe ENT_IGNORE behaviour).
        $invalid = "\xC3\x28"; // invalid 2-byte sequence
        $output  = $this->escaper->escHtml($invalid);

        self::assertNotSame('', $output);
    }
}
