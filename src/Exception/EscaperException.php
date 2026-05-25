<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Exception;

use RuntimeException;

/**
 * Base exception for all escaper-related failures.
 *
 * Catch this type to handle any failure originating from the escaper,
 * regardless of the specific cause.
 */
class EscaperException extends RuntimeException
{
}
