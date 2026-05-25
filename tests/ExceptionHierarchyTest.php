<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Tests;

use InitPHP\Escaper\Exception\EncodingConversionException;
use InitPHP\Escaper\Exception\EncodingNotSupportedException;
use InitPHP\Escaper\Exception\EscaperException;
use InitPHP\Escaper\Exception\InvalidContextException;
use InitPHP\Escaper\Exception\InvalidUtf8Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Locks the package exception tree in place so a future refactor that
 * accidentally re-parents an exception fails loudly.
 */
final class ExceptionHierarchyTest extends TestCase
{
    public function testBaseExtendsRuntimeException(): void
    {
        self::assertTrue(is_subclass_of(EscaperException::class, RuntimeException::class));
    }

    public function testEncodingNotSupportedExtendsBase(): void
    {
        self::assertTrue(is_subclass_of(EncodingNotSupportedException::class, EscaperException::class));
    }

    public function testEncodingConversionExtendsBase(): void
    {
        self::assertTrue(is_subclass_of(EncodingConversionException::class, EscaperException::class));
    }

    public function testInvalidContextExtendsBase(): void
    {
        self::assertTrue(is_subclass_of(InvalidContextException::class, EscaperException::class));
    }

    public function testInvalidUtf8ExtendsBase(): void
    {
        self::assertTrue(is_subclass_of(InvalidUtf8Exception::class, EscaperException::class));
    }
}
