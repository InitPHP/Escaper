# API Reference

Every public symbol in `initphp/escaper` 2.0, in one page. For prose explanations and worked examples, follow the per-page links.

## Namespace map

| Namespace                                   | Contains                                                                          |
| ------------------------------------------- | --------------------------------------------------------------------------------- |
| `InitPHP\Escaper`                           | `Esc` static facade, `Escaper` class.                                              |
| `InitPHP\Escaper\Exception`                 | `EscaperException` base + four subclasses.                                         |

---

## `InitPHP\Escaper\Esc`

Static facade — see [Esc Facade](Esc-Facade.md) for the full discussion.

### `Esc::esc()`

```php
public static function esc(
    mixed $data,
    string $context = 'html',
    ?string $encoding = null
): mixed;
```

| Parameter   | Type           | Notes                                                                         |
| ----------- | -------------- | ----------------------------------------------------------------------------- |
| `$data`     | `mixed`        | String → escaped. Array → recursed. Anything else → returned unchanged.        |
| `$context`  | `string`       | `html` (default), `attr`, `js`, `css`, `url`, `raw`, or `''`. Case-insensitive.|
| `$encoding` | `?string`      | Supported encoding name, or `null` for UTF-8. See [Encodings](Encodings.md).      |

**Returns**: `mixed` — escaped string, recursed array, or the input unchanged.

**Throws**: `InvalidContextException`, `EncodingNotSupportedException`, `EncodingConversionException`, `InvalidUtf8Exception`. All extend [`EscaperException`](Exceptions.md).

### `Esc::reset()`

```php
public static function reset(): void;
```

Drops every memoised `Escaper` from the per-encoding cache. Intended for tests and for explicit teardown in long-running PHP environments. See [Security Notes → Long-running PHP](Security-Notes.md#8-long-running-php-swoole--roadrunner--octane--frankenphp).

---

## `InitPHP\Escaper\Escaper`

The object behind the facade — see [Escaper Class](Escaper-Class.md) for the full discussion.

### `__construct()`

```php
public function __construct(?string $encoding = null);
```

| Parameter   | Type      | Notes                                                                                                   |
| ----------- | --------- | ------------------------------------------------------------------------------------------------------- |
| `$encoding` | `?string` | Lower-cased; `null` and `''` resolve to `'utf-8'`. Validated against the [supported list](Encodings.md#supported-encodings). |

**Throws**: `EncodingNotSupportedException` when the name is not whitelisted.

### `getEncoding()`

```php
public function getEncoding(): string;
```

Returns the lower-cased encoding name in effect for this instance.

### `escHtml()`

```php
public function escHtml(string $string): string;
```

Escapes for output between HTML tags. Calls `htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $encoding)`.

**Returns**: escaped string. **Never throws.**

See [HTML body context](Context-HTML.md).

### `escHtmlAttr()`

```php
public function escHtmlAttr(string $str): string;
```

Escapes for use inside an HTML attribute. Safe in quoted, single-quoted and unquoted attribute values.

**Returns**: escaped string.

**Throws**: `InvalidUtf8Exception`, `EncodingConversionException`.

See [HTML attribute context](Context-HTML-Attribute.md).

### `escJs()`

```php
public function escJs(string $str): string;
```

Escapes for embedding inside a JavaScript string literal. The caller is responsible for the surrounding quotes.

**Returns**: escaped string.

**Throws**: `InvalidUtf8Exception`, `EncodingConversionException`.

See [JavaScript context](Context-JavaScript.md).

### `escCss()`

```php
public function escCss(string $str): string;
```

Escapes for use inside a CSS value. Output uses the `\HEX ` form with the mandatory trailing space.

**Returns**: escaped string.

**Throws**: `InvalidUtf8Exception`, `EncodingConversionException`.

See [CSS context](Context-CSS.md).

### `escUrl()`

```php
public function escUrl(string $str): string;
```

Percent-encodes for use inside a single URL component. Thin wrapper around `rawurlencode()`.

**Returns**: percent-encoded string. **Never throws.**

See [URL context](Context-URL.md).

---

## `InitPHP\Escaper\Exception\*`

Full discussion in [Exceptions](Exceptions.md).

```php
namespace InitPHP\Escaper\Exception;

class EscaperException                 extends \RuntimeException {}

class EncodingNotSupportedException    extends EscaperException  {}
class EncodingConversionException      extends EscaperException  {}
class InvalidContextException          extends EscaperException  {}
class InvalidUtf8Exception             extends EscaperException  {}
```

### When each one fires

| Exception                          | Origin                                          | Common cause                                              |
| ---------------------------------- | ----------------------------------------------- | --------------------------------------------------------- |
| `EncodingNotSupportedException`    | `Escaper::__construct()`                        | Encoding name not in the whitelist.                        |
| `EncodingConversionException`      | `Escaper` internals (attribute/JS/CSS contexts) | iconv/mbstring unavailable, or returned `false`.          |
| `InvalidContextException`          | `Esc::esc()`                                    | Unknown `$context` argument (after lower-casing).         |
| `InvalidUtf8Exception`             | `escHtmlAttr`, `escJs`, `escCss`                | Malformed UTF-8 bytes the matcher cannot address.         |

---

## Cheat sheet

```php
use InitPHP\Escaper\Esc;
use InitPHP\Escaper\Escaper;
use InitPHP\Escaper\Exception\EscaperException;

// One-liner (most uses)
echo Esc::esc($value, 'html');

// Per-encoding instance (view-layer injection)
$escaper = new Escaper('windows-1252');
echo $escaper->escHtmlAttr($value);

// Recursive array escape
$safe = Esc::esc($payload, 'html');

// Failure handling
try {
    echo Esc::esc($value, 'attr', $encoding);
} catch (EscaperException $e) {
    error_log($e->getMessage());
}

// Reset facade cache (rarely needed)
Esc::reset();
```

## Supported context names

| Name      | Method on `Escaper`  | Page                                          |
| --------- | -------------------- | --------------------------------------------- |
| `html`    | `escHtml`            | [HTML body](Context-HTML.md)                     |
| `attr`    | `escHtmlAttr`        | [HTML attribute](Context-HTML-Attribute.md)      |
| `js`      | `escJs`              | [JavaScript](Context-JavaScript.md)              |
| `css`     | `escCss`             | [CSS](Context-CSS.md)                            |
| `url`     | `escUrl`             | [URL](Context-URL.md)                            |
| `raw`     | —                    | Returns input unchanged.                       |
| `''`      | —                    | Treated like `raw`.                            |

## Supported encoding names

The full list — every alias resolves to the same internal encoding once lower-cased. See [Encodings](Encodings.md) for the conversion pipeline.

```
iso-8859-1, iso8859-1, iso-8859-5, iso8859-5, iso-8859-15, iso8859-15,
utf-8,
cp866, ibm866, 866,
cp1251, windows-1251, win-1251, 1251,
cp1252, windows-1252, 1252,
koi8-r, koi8-ru, koi8r,
big5, 950, gb2312, 936, big5-hkscs,
shift_jis, sjis, sjis-win, cp932, 932,
euc-jp, eucjp, eucjp-win,
macroman
```
