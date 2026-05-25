<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Tests;

use InitPHP\Escaper\Escaper;
use PHPUnit\Framework\TestCase;

final class EscaperUrlTest extends TestCase
{
    private Escaper $escaper;

    protected function setUp(): void
    {
        $this->escaper = new Escaper();
    }

    public function testEmptyStringReturnsEmptyString(): void
    {
        self::assertSame('', $this->escaper->escUrl(''));
    }

    public function testRfc3986UnreservedCharactersAreNotEncoded(): void
    {
        self::assertSame('Hello.world-1_2~3', $this->escaper->escUrl('Hello.world-1_2~3'));
    }

    public function testSpaceIsPercentEncodedAsPercent20(): void
    {
        // rawurlencode (RFC 3986) — not "+" like urlencode.
        self::assertSame('foo%20bar', $this->escaper->escUrl('foo bar'));
    }

    public function testJavascriptInjectionVectorIsPercentEncoded(): void
    {
        $input  = '" onmouseover="alert(\'hello\')';
        $output = $this->escaper->escUrl($input);

        self::assertSame(
            '%22%20onmouseover%3D%22alert%28%27hello%27%29',
            $output
        );
    }
}
