# Exceptions

Every exception thrown by the escaper extends a single base, so one `catch` block can handle anything originating from this package.

## The tree

```
\RuntimeException
    └─ InitPHP\Escaper\Exception\EscaperException
        ├─ EncodingNotSupportedException
        ├─ EncodingConversionException
        ├─ InvalidContextException
        └─ InvalidUtf8Exception
```

All five live under `InitPHP\Escaper\Exception\`.

## Catching everything

```php
use InitPHP\Escaper\Esc;
use InitPHP\Escaper\Exception\EscaperException;

try {
    echo Esc::esc($value, 'attr', $encoding);
} catch (EscaperException $e) {
    error_log('escaper failure: ' . $e->getMessage());
    echo '[escape failed]';
}
```

Because the base extends `\RuntimeException`, existing `catch (\RuntimeException)` or `catch (\Throwable)` blocks still work — useful when upgrading from 1.x where the package threw plain `\Exception` (see the [Migration Guide](Migration-Guide.md#2-replace-catch-exception-e-blocks-recommended)).

## Per-exception reference

### `EscaperException`

```php
namespace InitPHP\Escaper\Exception;

class EscaperException extends \RuntimeException
{
}
```

The base. Catch this to handle any escaper failure without caring about the cause. Never thrown directly — always one of the four subclasses below.

---

### `EncodingNotSupportedException`

**Thrown by**: `Escaper::__construct()` (and therefore by `Esc::esc()` the first time a new encoding is requested).

**When**: the encoding name passed to the constructor is not in the [supported whitelist](Encodings.md#supported-encodings).

**Message format**: `Encoding "<name>" is not supported.`

```php
new Escaper('utf-16');
// EncodingNotSupportedException: Encoding "utf-16" is not supported.

Esc::esc('a', 'html', 'not-a-real-encoding');
// EncodingNotSupportedException: Encoding "not-a-real-encoding" is not supported.
```

The lookup is case-insensitive — the lower-cased name is what appears in the message.

---

### `EncodingConversionException`

**Thrown by**: `Escaper::convertEncoding()` (used internally by all context escapers except `escHtml()` and `escUrl()` when the configured encoding is not UTF-8).

**When**:

- **Neither** `ext-iconv` **nor** `ext-mbstring` is loaded.
- The underlying iconv / mbstring call returns `false` at runtime.

**Message formats**:

- `Either ext-iconv or ext-mbstring is required to convert string encodings.`
- `Failed to convert string from "<from>" to "<to>".`

```php
// Hypothetical: input cannot be round-tripped through windows-1252.
(new Escaper('windows-1252'))->escHtmlAttr($problematicBytes);
// EncodingConversionException: Failed to convert string from "windows-1252" to "UTF-8".
```

In 1.x the same failure silently substituted an empty string. The 2.0 behaviour is strict — see the [Migration Guide](Migration-Guide.md#3-encoding-conversion-failure-now-throws-behavioural-break).

---

### `InvalidContextException`

**Thrown by**: `Esc::esc()`.

**When**: the `$context` argument is not one of `html`, `attr`, `js`, `css`, `url`, `raw` (case-insensitive). The empty string and `raw` are explicitly accepted and short-circuit to return the input unchanged.

**Message format**: `Invalid escape context "<context>".`

```php
Esc::esc('<b>', 'xml');
// InvalidContextException: Invalid escape context "xml".
```

The lower-cased name appears in the message.

---

### `InvalidUtf8Exception`

**Thrown by**: `Escaper::escHtmlAttr()`, `Escaper::escJs()`, `Escaper::escCss()`.

**When**: the input is not — and cannot be converted to — well-formed UTF-8.

**Message format**: `String to be escaped was not valid UTF-8 or could not be converted.`

```php
(new Escaper())->escHtmlAttr("\xC3\x28");
// InvalidUtf8Exception: String to be escaped was not valid UTF-8 or could not be converted.
```

`escHtml()` does **not** raise this exception — it uses `htmlspecialchars()` with `ENT_SUBSTITUTE`, which replaces malformed bytes with `U+FFFD` instead of failing. `escUrl()` is byte-oriented and never validates UTF-8 either.

---

## Granular catch — example

```php
use InitPHP\Escaper\Exception\{
    EncodingConversionException,
    EncodingNotSupportedException,
    InvalidContextException,
    InvalidUtf8Exception,
};

try {
    $safe = Esc::esc($value, $context, $encoding);
} catch (EncodingNotSupportedException $e) {
    // Bug in the calling code — configured encoding name is wrong.
    throw $e;
} catch (InvalidContextException $e) {
    // Bug in the calling code — context name is wrong.
    throw $e;
} catch (InvalidUtf8Exception | EncodingConversionException $e) {
    // Bad input — log and fall back.
    error_log($e->getMessage());
    $safe = '[invalid value]';
}
```

In practice the first two are programmer errors that should never reach production once the calling code is correct, while the last two reflect data issues that you may want to handle gracefully.

## See also

- [Encodings](Encodings.md) — the conversion pipeline and where `EncodingConversionException` is raised.
- [`Esc` facade](Esc-Facade.md) — where `InvalidContextException` is raised.
- [Migration Guide](Migration-Guide.md) — what changed from 1.x.
