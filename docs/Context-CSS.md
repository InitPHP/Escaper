# CSS context (`escCss`)

> Use when the value lands **inside a CSS property value**:
> `color: HERE;`, `background-image: url(HERE);`, `<style>div { color: HERE; }</style>`.

## What it does

`escCss()` whitelists `[A-Za-z0-9]`. Every other character is rewritten as the CSS escape sequence `\HEX ` — with the **mandatory trailing space** that terminates the escape.

The trailing space looks redundant but it is required by the CSS spec: without it, the parser would eat hex-digit-looking characters that follow the escape into the same token.

## Signature

```php
public function escCss(string $str): string;
```

Or via the facade:

```php
Esc::esc(string $str, 'css', ?string $encoding = null): string;
```

### Exceptions

| Throws                          | When                                                          |
| ------------------------------- | ------------------------------------------------------------- |
| `InvalidUtf8Exception`          | `$str` is not valid UTF-8 (after any encoding conversion).    |
| `EncodingConversionException`   | iconv / mbstring fail during UTF-8 conversion.                |

## Examples

### Plain alphanumeric passes through

```php
Esc::esc('red', 'css');         // red
Esc::esc('10px', 'css');        // 10px
Esc::esc('abcXYZ123', 'css');   // abcXYZ123
```

Note: even `#` and units like `%`, `em` get escaped — `escCss` is intentionally strict because it cannot tell value from selector syntax.

```php
Esc::esc('#ff0000', 'css');     // \23 ff0000
Esc::esc('expression(alert(1))', 'css');
// expression\28 alert\28 1\29 \29
```

### Preventing a `</style>` breakout

```php
$untrusted = '</style><script>alert(1)</script>';

Esc::esc($untrusted, 'css');
// \3C \2F style\3E \3C script\3E alert\28 1\29 \3C \2F script\3E
```

`<`, `>`, `/`, parentheses and spaces all turn into CSS escapes, so the attacker cannot close the `<style>` block and inject a script.

### Single-byte specials

```php
Esc::esc(' ', 'css');   // \20 
Esc::esc('{', 'css');   // \7B 
Esc::esc('}', 'css');   // \7D 
Esc::esc("'", 'css');   // \27 
Esc::esc('"', 'css');   // \22 
```

### Multibyte characters

```php
Esc::esc('ş', 'css');   // \15F 
Esc::esc('🚀', 'css');  // \1F680 
```

Both come out as single CSS escape sequences regardless of how many UTF-8 bytes they used in the input.

### Empty / digit-only short-circuits

```php
Esc::esc('', 'css');    // ''
Esc::esc('42', 'css');  // 42
```

## When **not** to use it

| Location                                | Why                                                                                                              | Use instead                                  |
| --------------------------------------- | ---------------------------------------------------------------------------------------------------------------- | -------------------------------------------- |
| Selectors built from user input         | `escCss` is for property **values**. Selectors built from user input are dangerous even with escaping — historic IE quirks like `expression()` could trigger script. | Don't. Use a fixed selector list. |
| `url(HERE)` where `HERE` is a URL       | The URL itself needs URL encoding before it's a candidate for CSS.                                               | [`escUrl`](Context-URL.md), then drop the result into a fixed CSS template. |
| Inline `style=""` attribute             | Two contexts at once: the value first needs to be HTML-attribute-safe, and the CSS inside needs to be CSS-safe.   | `escCss` on the CSS expression, then rely on the attribute quoting. The fixer below works. |
| `@import` URLs                          | Same as `url(...)` — encode the URL with `escUrl` first.                                                          | [`escUrl`](Context-URL.md)                      |

### Inline `style` attribute — worked example

```php
$color = $untrustedColor;                          // e.g. "red; background: url(...)"
$css   = "color: " . Esc::esc($color, 'css') . ";";

echo '<div style="' . $css . '">…</div>';
// <div style="color: red\3B \20 background\3A \20 url\28 \2E \2E \2E \29 ;">…</div>
```

The attribute quoting (`style="…"`) protects the surrounding HTML attribute parser; `escCss` protects the contents from being interpreted as CSS code.

## Why every value goes through UTF-8 internally

Same as the [attribute context](Context-HTML-Attribute.md#why-every-value-goes-through-utf-8-internally) — the matcher needs to address full code points. See [Encodings](Encodings.md).

## See also

- [HTML attribute context](Context-HTML-Attribute.md) — for the `style=""` wrapper
- [URL context](Context-URL.md) — for `url(...)` values
- [Security Notes](Security-Notes.md)
- [API Reference → `escCss`](API-Reference.md#esccss)
