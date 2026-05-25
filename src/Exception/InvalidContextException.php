<?php

declare(strict_types=1);

namespace InitPHP\Escaper\Exception;

/**
 * Thrown by {@see \InitPHP\Escaper\Esc::esc()} when an unknown escape context
 * is requested (anything other than html, attr, js, css, url, raw).
 */
class InvalidContextException extends EscaperException
{
}
