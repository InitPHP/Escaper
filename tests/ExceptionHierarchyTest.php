<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Tests;

use InitPHP\Escaper\Exception\EncodingConversionException;
use InitPHP\Escaper\Exception\EncodingNotSupportedException;
use InitPHP\Escaper\Exception\EscaperException;
use InitPHP\Escaper\Exception\InvalidContextException;
use InitPHP\Escaper\Exception\InvalidUtf8Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * Locks the package exception tree in place so a future refactor that
 * accidentally re-parents an exception fails loudly.
 *
 * Uses reflection rather than `is_subclass_of()` so the assertions look at
 * the *immediate* parent of each exception, not just somewhere in the chain.
 */
final class ExceptionHierarchyTest extends TestCase
{
    public function testBaseExtendsRuntimeException(): void
    {
        self::assertSame(RuntimeException::class, self::parentOf(EscaperException::class));
    }

    public function testEncodingNotSupportedExtendsBase(): void
    {
        self::assertSame(EscaperException::class, self::parentOf(EncodingNotSupportedException::class));
    }

    public function testEncodingConversionExtendsBase(): void
    {
        self::assertSame(EscaperException::class, self::parentOf(EncodingConversionException::class));
    }

    public function testInvalidContextExtendsBase(): void
    {
        self::assertSame(EscaperException::class, self::parentOf(InvalidContextException::class));
    }

    public function testInvalidUtf8ExtendsBase(): void
    {
        self::assertSame(EscaperException::class, self::parentOf(InvalidUtf8Exception::class));
    }

    /**
     * @param class-string $class
     */
    private static function parentOf(string $class): string
    {
        $parent = (new ReflectionClass($class))->getParentClass();
        self::assertNotFalse($parent, \sprintf('Class "%s" must have a parent.', $class));

        return $parent->getName();
    }
}
