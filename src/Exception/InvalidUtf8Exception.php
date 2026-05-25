<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Exception;

/**
 * Thrown when a string is not — and cannot be converted to — valid UTF-8
 * before being passed to a context escaper.
 */
class InvalidUtf8Exception extends EscaperException
{
}
