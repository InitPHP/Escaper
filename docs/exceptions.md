# Exceptions

Every exception thrown by the escaper extends a single base, so a
single `catch` block can handle anything originating from this
package.

```
\RuntimeException
    └─ InitPHP\Escaper\Exception\EscaperException
        ├─ EncodingNotSupportedException
        ├─ EncodingConversionException
        ├─ InvalidContextException
        └─ InvalidUtf8Exception
```

## `EscaperException`

The base. Catch this to handle any escaper failure without caring
about the cause.

```php
use InitPHP\Escaper\Esc;
use InitPHP\Escaper\Exception\EscaperException;

try {
    echo Esc::esc($value, 'attr', $encoding);
} catch (EscaperException $e) {
    // log and fall back
}
```

## `EncodingNotSupportedException`

Thrown from `new Escaper($encoding)` when `$encoding` is not part of
the supported whitelist. Also surfaces from `Esc::esc()` the first
time a new encoding is requested.

```php
new Escaper('utf-16');
// EncodingNotSupportedException: Encoding "utf-16" is not supported.
```

## `EncodingConversionException`

Thrown by `Escaper` when:

- neither `ext-iconv` nor `ext-mbstring` is loaded, **or**
- the underlying conversion call returns `false`.

The previous (pre-2.x) behaviour silently substituted an empty string
in this case, which could mask real failures. The exception now
prevents that data loss.

## `InvalidContextException`

Thrown by `Esc::esc()` when `$context` is not one of `html`, `attr`,
`js`, `css`, `url`, `raw` (case-insensitive). Empty string and `raw`
are accepted and return the input unchanged.

## `InvalidUtf8Exception`

Thrown by the context escapers (`escHtmlAttr`, `escJs`, `escCss`)
when the input is not — and cannot be converted to — well-formed
UTF-8. `escHtml` does not raise this exception; it leans on
`htmlspecialchars()` with `ENT_SUBSTITUTE`, which replaces malformed
bytes with `U+FFFD` instead of failing.
