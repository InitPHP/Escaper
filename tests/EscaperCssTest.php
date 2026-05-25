<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Tests;

use InitPHP\Escaper\Escaper;
use PHPUnit\Framework\TestCase;

final class EscaperCssTest extends TestCase
{
    private Escaper $escaper;

    protected function setUp(): void
    {
        $this->escaper = new Escaper();
    }

    public function testEmptyStringShortCircuits(): void
    {
        self::assertSame('', $this->escaper->escCss(''));
    }

    public function testDigitsOnlyShortCircuits(): void
    {
        self::assertSame('42', $this->escaper->escCss('42'));
    }

    public function testAlphanumericPassesThroughUnchanged(): void
    {
        self::assertSame('abcXYZ123', $this->escaper->escCss('abcXYZ123'));
    }

    public function testSingleByteSpecialCharsBecomeHexEscape(): void
    {
        // CSS escape is "\HEX " with a mandatory terminating space.
        self::assertSame('\\20 ', $this->escaper->escCss(' '));
        self::assertSame('\\7B ', $this->escaper->escCss('{'));
        self::assertSame('\\7D ', $this->escaper->escCss('}'));
        self::assertSame('\\27 ', $this->escaper->escCss("'"));
        self::assertSame('\\22 ', $this->escaper->escCss('"'));
    }

    public function testStyleBreakoutVectorIsEscaped(): void
    {
        $input  = '</style><script>alert(1)</script>';
        $output = $this->escaper->escCss($input);

        self::assertSame(
            '\\3C \\2F style\\3E \\3C script\\3E alert\\28 1\\29 \\3C \\2F script\\3E ',
            $output
        );
    }

    public function testBmpMultibyteCharacterBecomesHexEscape(): void
    {
        // U+015F LATIN SMALL LETTER S WITH CEDILLA (ş)
        self::assertSame('\\15F ', $this->escaper->escCss('ş'));
    }

    public function testSupplementaryPlaneCharacterBecomesHexEscape(): void
    {
        // U+1F680 → 1F680 in hex.
        self::assertSame('\\1F680 ', $this->escaper->escCss('🚀'));
    }
}
