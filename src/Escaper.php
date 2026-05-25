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

use InitPHP\Escaper\Exception\EncodingConversionException;
use InitPHP\Escaper\Exception\EncodingNotSupportedException;
use InitPHP\Escaper\Exception\InvalidUtf8Exception;

use function bin2hex;
use function ctype_digit;
use function hexdec;
use function htmlspecialchars;
use function iconv;
use function mb_convert_encoding;
use function preg_match;
use function preg_replace_callback;
use function rawurlencode;
use function strtolower;
use function strtoupper;
use function substr;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * Context-aware output escaper.
 *
 * Provides escape routines for the five untrusted-data contexts described in
 * the OWASP XSS Prevention Cheat Sheet:
 *
 * - HTML body                  ({@see escHtml()})
 * - HTML common attribute      ({@see escHtmlAttr()})
 * - JavaScript string literal  ({@see escJs()})
 * - CSS value                  ({@see escCss()})
 * - URL parameter              ({@see escUrl()})
 *
 * All input must be — or be convertible to — valid UTF-8. The output is
 * returned in the encoding that was configured at construction time
 * (UTF-8 by default).
 */
class Escaper
{
    /**
     * Map of code points that have a shorter named HTML entity which we prefer
     * over the numeric form when emitted from an attribute matcher.
     *
     * @var array<int, string>
     */
    private const HTML_NAMED_ENTITY_MAP = [
        34 => 'quot',
        38 => 'amp',
        60 => 'lt',
        62 => 'gt',
    ];

    /**
     * Flags passed to {@see htmlspecialchars()} in {@see escHtml()}.
     *
     * ENT_QUOTES escapes both single and double quotes; ENT_SUBSTITUTE
     * replaces invalid byte sequences with U+FFFD rather than returning an
     * empty string.
     */
    private const HTML_SPECIAL_CHARS_FLAGS = ENT_QUOTES | ENT_SUBSTITUTE;

    /**
     * Whitelist of encodings recognised by the constructor. Membership is
     * tested after the input is lower-cased.
     *
     * @var list<string>
     */
    private const SUPPORTED_ENCODINGS = [
        'iso-8859-1',
        'iso8859-1',
        'iso-8859-5',
        'iso8859-5',
        'iso-8859-15',
        'iso8859-15',
        'utf-8',
        'cp866',
        'ibm866',
        '866',
        'cp1251',
        'windows-1251',
        'win-1251',
        '1251',
        'cp1252',
        'windows-1252',
        '1252',
        'koi8-r',
        'koi8-ru',
        'koi8r',
        'big5',
        '950',
        'gb2312',
        '936',
        'big5-hkscs',
        'shift_jis',
        'sjis',
        'sjis-win',
        'cp932',
        '932',
        'euc-jp',
        'eucjp',
        'eucjp-win',
        'macroman',
    ];

    /**
     * The lower-cased output encoding used by {@see fromUtf8()} and reported
     * by {@see getEncoding()}.
     */
    protected string $encoding = 'utf-8';

    /**
     * @param string|null $encoding Output encoding name. When null or empty,
     *                              UTF-8 is used. Lookup is case-insensitive.
     *
     * @throws EncodingNotSupportedException When $encoding is not part of the
     *                                       supported whitelist.
     */
    public function __construct(?string $encoding = null)
    {
        if ($encoding !== null && $encoding !== '') {
            $encoding = strtolower($encoding);
            if (!\in_array($encoding, self::SUPPORTED_ENCODINGS, true)) {
                throw new EncodingNotSupportedException(
                    \sprintf('Encoding "%s" is not supported.', $encoding)
                );
            }
            $this->encoding = $encoding;
        }
    }

    /**
     * @return string The lower-cased output encoding.
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * Escape a string for output between HTML body tags.
     *
     * Suitable for: `<div>HERE</div>`, `<p>HERE</p>`.
     *
     * Not suitable for attributes, scripts, styles or URLs — use the dedicated
     * helper for each of those contexts.
     */
    public function escHtml(string $string): string
    {
        return htmlspecialchars($string, self::HTML_SPECIAL_CHARS_FLAGS, $this->encoding);
    }

    /**
     * Escape a string for use inside an HTML attribute value.
     *
     * Safe for quoted, single-quoted and unquoted attribute values. Every
     * character outside `[A-Za-z0-9,.\-_]` is rewritten as a numeric or named
     * HTML entity.
     *
     * @throws InvalidUtf8Exception        When the input cannot be expressed as UTF-8.
     * @throws EncodingConversionException When iconv/mbstring fail to convert.
     */
    public function escHtmlAttr(string $str): string
    {
        $str = $this->toUtf8($str);
        if ($str === '' || ctype_digit($str)) {
            return $str;
        }
        $result = preg_replace_callback(
            '/[^a-z0-9,\.\-_]/iSu',
            [$this, 'htmlAttrMatcher'],
            $str
        );

        return $this->fromUtf8($result ?? '');
    }

    /**
     * Escape a string so it can be embedded inside a JavaScript string literal.
     *
     * The caller is responsible for wrapping the result in matching quotes,
     * for example `var foo = "<?= $esc->escJs($value) ?>";`.
     *
     * @throws InvalidUtf8Exception        When the input cannot be expressed as UTF-8.
     * @throws EncodingConversionException When iconv/mbstring fail to convert.
     */
    public function escJs(string $str): string
    {
        $str = $this->toUtf8($str);
        if ($str === '' || ctype_digit($str)) {
            return $str;
        }
        $result = preg_replace_callback(
            '/[^a-z0-9,\._]/iSu',
            [$this, 'jsMatcher'],
            $str
        );

        return $this->fromUtf8($result ?? '');
    }

    /**
     * Percent-encode a string for safe inclusion in a URL component.
     *
     * Thin wrapper around {@see rawurlencode()} kept here so callers can reach
     * every escape context through a single object.
     */
    public function escUrl(string $str): string
    {
        return rawurlencode($str);
    }

    /**
     * Escape a string for use inside a CSS value.
     *
     * Every non-alphanumeric character is rewritten as `\HEX ` (with the
     * trailing space being the CSS escape terminator).
     *
     * @throws InvalidUtf8Exception        When the input cannot be expressed as UTF-8.
     * @throws EncodingConversionException When iconv/mbstring fail to convert.
     */
    public function escCss(string $str): string
    {
        $str = $this->toUtf8($str);
        if ($str === '' || ctype_digit($str)) {
            return $str;
        }
        $result = preg_replace_callback(
            '/[^a-z0-9]/iSu',
            [$this, 'cssMatcher'],
            $str
        );

        return $this->fromUtf8($result ?? '');
    }

    /**
     * Callback for {@see escHtmlAttr()}.
     *
     * @param array<int, string> $matches
     */
    protected function htmlAttrMatcher(array $matches): string
    {
        $chr = $matches[0];
        if (\strlen($chr) > 1) {
            $chr = $this->convertEncoding($chr, 'UTF-32BE', 'UTF-8');
        }

        $ord = (int) hexdec(bin2hex($chr));

        // C0 (except tab/LF/CR) and C1 controls are not valid in HTML and
        // must be replaced with the Unicode replacement character. The check
        // is performed on the decoded code point so it also catches U+0080
        // through U+009F when they arrive in multibyte UTF-8 form.
        if (
            ($ord <= 0x1F && $ord !== 0x09 && $ord !== 0x0A && $ord !== 0x0D)
            || ($ord >= 0x7F && $ord <= 0x9F)
        ) {
            return '&#xFFFD;';
        }

        if (isset(self::HTML_NAMED_ENTITY_MAP[$ord])) {
            return '&' . self::HTML_NAMED_ENTITY_MAP[$ord] . ';';
        }

        if ($ord > 255) {
            return \sprintf('&#x%04X;', $ord);
        }

        return \sprintf('&#x%02X;', $ord);
    }

    /**
     * Callback for {@see escJs()}. Emits `\xNN` for single-byte characters
     * and `\uXXXX` (or surrogate-pair `\uXXXX\uXXXX`) for multibyte ones.
     *
     * @param array<int, string> $matches
     */
    protected function jsMatcher(array $matches): string
    {
        $chr = $matches[0];
        if (\strlen($chr) === 1) {
            return \sprintf('\\x%02X', \ord($chr));
        }

        $chr = $this->convertEncoding($chr, 'UTF-16BE', 'UTF-8');
        $hex = strtoupper(bin2hex($chr));

        if (\strlen($hex) <= 4) {
            return \sprintf('\\u%04s', $hex);
        }

        $highSurrogate = substr($hex, 0, 4);
        $lowSurrogate  = substr($hex, 4, 4);

        return \sprintf('\\u%04s\\u%04s', $highSurrogate, $lowSurrogate);
    }

    /**
     * Callback for {@see escCss()}. Emits `\HEX ` with the mandatory trailing
     * space that terminates a CSS escape sequence.
     *
     * @param array<int, string> $matches
     */
    protected function cssMatcher(array $matches): string
    {
        $chr = $matches[0];
        if (\strlen($chr) === 1) {
            $ord = \ord($chr);
        } else {
            $chr = $this->convertEncoding($chr, 'UTF-32BE', 'UTF-8');
            $ord = (int) hexdec(bin2hex($chr));
        }

        return \sprintf('\\%X ', $ord);
    }

    /**
     * Convert the input to UTF-8 (if it is not already) and assert that the
     * result is well-formed.
     *
     * @throws InvalidUtf8Exception        When the result is not valid UTF-8.
     * @throws EncodingConversionException When iconv/mbstring fail to convert.
     */
    protected function toUtf8(string $string): string
    {
        if ($this->encoding === 'utf-8') {
            $result = $string;
        } else {
            $result = $this->convertEncoding($string, 'UTF-8', $this->encoding);
        }

        if (!$this->isUtf8($result)) {
            throw new InvalidUtf8Exception(
                'String to be escaped was not valid UTF-8 or could not be converted.'
            );
        }

        return $result;
    }

    /**
     * Convert a UTF-8 string back to the configured output encoding.
     *
     * @throws EncodingConversionException When iconv/mbstring fail to convert.
     */
    protected function fromUtf8(string $str): string
    {
        if ($this->encoding === 'utf-8') {
            return $str;
        }

        return $this->convertEncoding($str, $this->encoding, 'UTF-8');
    }

    /**
     * Tell whether a string is well-formed UTF-8.
     */
    protected function isUtf8(string $str): bool
    {
        return $str === '' || preg_match('/^./su', $str) === 1;
    }

    /**
     * Convert a string between two encodings using iconv (preferred) or
     * mbstring (fallback).
     *
     * @param string $str  The source string.
     * @param string $to   The target encoding name.
     * @param string $from The source encoding name.
     *
     * @throws EncodingConversionException When neither extension is available,
     *                                     or when the conversion fails.
     */
    protected function convertEncoding(string $str, string $to, string $from): string
    {
        if (\function_exists('iconv')) {
            $result = @iconv($from, $to, $str);
        } elseif (\function_exists('mb_convert_encoding')) {
            $result = @mb_convert_encoding($str, $to, $from);
        } else {
            throw new EncodingConversionException(
                'Either ext-iconv or ext-mbstring is required to convert string encodings.'
            );
        }

        if ($result === false) {
            throw new EncodingConversionException(
                \sprintf('Failed to convert string from "%s" to "%s".', $from, $to)
            );
        }

        return $result;
    }
}
