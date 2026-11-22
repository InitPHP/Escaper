# HTML body context (`escHtml`)

> Use when the value lands **between HTML tags**:
> `<p>HERE</p>`, `<div>HERE</div>`, `<li>HERE</li>`, `<title>HERE</title>`.

## What it does

`escHtml()` is a thin wrapper around PHP's built-in `htmlspecialchars()`, called with:

```php
htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $this->encoding);
```

The flag combination matters:

- `ENT_QUOTES` — escapes both `"` and `'`. The result is therefore safe to drop into an attribute value *as well as* a body. (`escHtmlAttr` is still the right choice for attributes — see [HTML attribute context](Context-HTML-Attribute.md) — but you won't get bitten if a snippet escaped for the body is later moved into an attribute.)
- `ENT_SUBSTITUTE` — replaces malformed UTF-8 byte sequences with `U+FFFD` instead of returning an empty string. This is **fail-loud-but-not-crash**: invalid input is preserved as the replacement character, not silently dropped.

The five characters that get escaped:

| Character | Replaced with |
| --------- | ------------- |
| `&`       | `&amp;`       |
| `<`       | `&lt;`        |
| `>`       | `&gt;`        |
| `"`       | `&quot;`      |
| `'`       | `&#039;`      |

Everything else passes through unchanged.

## Signature

```php
public function escHtml(string $string): string;
```

Or via the facade:

```php
Esc::esc(string $string, 'html', ?string $encoding = null): string;
```

`escHtml()` never throws — `htmlspecialchars()` with `ENT_SUBSTITUTE` always returns a string.

## Examples

```php
use InitPHP\Escaper\Esc;

echo Esc::esc('<script>alert(1)</script>');
// &lt;script&gt;alert(1)&lt;/script&gt;

echo Esc::esc('Tom & Jerry');
// Tom &amp; Jerry

echo Esc::esc('A "quoted" word and a \'single-quoted\' one');
// A &quot;quoted&quot; word and a &#039;single-quoted&#039; one

echo Esc::esc('Merhaba dünya 🚀');
// Merhaba dünya 🚀
```

Multibyte characters (Turkish, emoji, CJK, etc.) are left alone in UTF-8 — they are not unsafe in the HTML body context.

### Empty and digit-only input

```php
Esc::esc('');       // ''
Esc::esc('12345');  // 12345
```

### Double-escaping is not detected

`escHtml()` does not know whether its input is already escaped. Calling it twice will encode the entities a second time:

```php
echo Esc::esc('Tom & Jerry');                        // Tom &amp; Jerry  (correct)
echo Esc::esc(Esc::esc('Tom & Jerry'));              // Tom &amp;amp; Jerry  (wrong)
echo Esc::esc('Already <b>escaped</b>? &amp; double');
// Already &lt;b&gt;escaped&lt;/b&gt;? &amp;amp; double
```

The fix is process discipline — store raw bytes, escape on output. See [Security Notes](Security-Notes.md#1-escape-on-output-not-on-input).

## When **not** to use it

| Location                     | Why `escHtml` is wrong                                                                          | Use instead                                  |
| ---------------------------- | ----------------------------------------------------------------------------------------------- | -------------------------------------------- |
| Unquoted attribute (`name=X`)| A space or `=` in the value can break out of the attribute.                                     | [`escHtmlAttr`](Context-HTML-Attribute.md)      |
| `<script>` body              | HTML entities are not decoded inside a script — the payload survives intact.                    | [`escJs`](Context-JavaScript.md)                |
| `<style>` body               | Same problem — CSS does not decode HTML entities.                                                | [`escCss`](Context-CSS.md)                      |
| `href=""` / `src=""` URL     | `&lt;` doesn't prevent `javascript:` schemes; only URL encoding + scheme validation does.        | [`escUrl`](Context-URL.md) (+ scheme validation)|
| Inline event handler (`onclick=""`) | Same as `<script>` — the contents are JavaScript, not HTML.                              | [`escJs`](Context-JavaScript.md)                |

## Encoding behaviour

If the `Escaper` is configured with a non-UTF-8 encoding, `htmlspecialchars()` is called with that encoding directly — no conversion round-trip happens for the body context (unlike the attribute / JS / CSS contexts, which require an internal UTF-8 trip):

```php
$escaper = new InitPHP\Escaper\Escaper('iso-8859-1');
$escaper->escHtml("\xE9");  // "\xE9"  — left alone, encoding stays ISO-8859-1
```

See [Encodings](Encodings.md) for the full list and what conversion implies.

## See also

- [HTML attribute context](Context-HTML-Attribute.md)
- [JavaScript context](Context-JavaScript.md)
- [Security Notes](Security-Notes.md)
- [API Reference → `escHtml`](API-Reference.md#eschtml)
