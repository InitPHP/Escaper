<?php

/**
 * This file is part of the initphp/escaper package.
 *
 * (c) InitPHP <info@muhammetsafak.com.tr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace InitPHP\Escaper;

use InitPHP\Escaper\Exception\InvalidContextException;

use function strtolower;

/**
 * Thin static facade over {@see Escaper}.
 *
 * Accepts a string or an array (recursively) and dispatches to the
 * appropriate {@see Escaper} method based on the requested context.
 * Per-encoding {@see Escaper} instances are memoised across calls.
 */
class Esc
{
    /**
     * Map of supported context names to the {@see Escaper} method that
     * implements them. The `raw` context is handled inline and bypasses the
     * escaper entirely.
     *
     * @var array<string, string>
     */
    private const CONTEXT_METHODS = [
        'html' => 'escHtml',
        'attr' => 'escHtmlAttr',
        'js'   => 'escJs',
        'css'  => 'escCss',
        'url'  => 'escUrl',
    ];

    /**
     * Memoised escaper instances, keyed by normalised encoding name.
     *
     * @var array<string, Escaper>
     */
    private static array $instances = [];

    /**
     * Escape a string — or every string inside an array — for the given
     * output context.
     *
     * Behaviour by input type:
     * - **string** — escaped according to `$context` and returned.
     * - **array** — every element is escaped recursively. Keys are not
     *   touched; non-string, non-array elements are returned unchanged.
     * - **anything else** — returned as-is.
     *
     * @param mixed       $data     The value to escape.
     * @param string      $context  One of `html`, `attr`, `js`, `css`,
     *                              `url`, `raw`. Lookup is case-insensitive.
     *                              The empty string is treated like `raw`.
     * @param string|null $encoding Output encoding; null resolves to UTF-8.
     *
     * @return mixed The escaped value, or the original value unchanged for
     *               unsupported types and `raw`/empty contexts.
     *
     * @throws InvalidContextException When `$context` is not a recognised name.
     */
    public static function esc($data, string $context = 'html', ?string $encoding = null)
    {
        if (\is_array($data)) {
            foreach ($data as &$value) {
                $value = self::esc($value, $context, $encoding);
            }
            unset($value);

            return $data;
        }

        if (!\is_string($data)) {
            return $data;
        }

        $context = strtolower($context);
        if ($context === '' || $context === 'raw') {
            return $data;
        }

        if (!isset(self::CONTEXT_METHODS[$context])) {
            throw new InvalidContextException(
                \sprintf('Invalid escape context "%s".', $context)
            );
        }

        $method = self::CONTEXT_METHODS[$context];

        return self::getEscaper($encoding)->{$method}($data);
    }

    /**
     * Reset the memoised escaper cache. Intended for tests; clears every
     * cached {@see Escaper} so the next {@see esc()} call rebuilds them.
     */
    public static function reset(): void
    {
        self::$instances = [];
    }

    /**
     * Return — and lazily create — the {@see Escaper} for the given encoding.
     * Null is normalised to UTF-8 so all callers share a single instance.
     */
    private static function getEscaper(?string $encoding): Escaper
    {
        $key = $encoding === null || $encoding === '' ? 'utf-8' : strtolower($encoding);

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new Escaper($encoding);
        }

        return self::$instances[$key];
    }
}
