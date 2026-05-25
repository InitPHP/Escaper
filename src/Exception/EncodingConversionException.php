<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Exception;

/**
 * Thrown when the underlying iconv or mbstring extension fails to convert a
 * string between two encodings, or when neither extension is available.
 */
class EncodingConversionException extends EscaperException
{
}
