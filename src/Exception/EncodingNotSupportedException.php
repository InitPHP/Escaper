<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Exception;

/**
 * Thrown when the encoding requested at construction time is not part of the
 * supported whitelist.
 */
class EncodingNotSupportedException extends EscaperException
{
}
