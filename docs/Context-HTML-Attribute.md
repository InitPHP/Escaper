# HTML attribute context (`escHtmlAttr`)

> Use when the value lands **inside an HTML attribute**:
> `<span title="HERE">`, `<a href="HERE">`, `<input value=HERE>`.

## What it does

`escHtmlAttr()` is strict by design. It whitelists `[A-Za-z0-9,.\-_]` — every other character is rewritten as an HTML entity. The output is therefore safe inside **quoted, single-quoted *and* unquoted** attribute values.

Three rules drive the output:

1. **C0 controls (`U+0000`–`U+001F`)** except tab/LF/CR → `&#xFFFD;` (the Unicode replacement character).
2. **C1 controls (`U+007F`–`U+009F`)** → `&#xFFFD;`. This check is performed on the decoded code point, so it catches the multibyte UTF-8 form (`\xC2\x80` … `\xC2\x9F`) too.
3. **Everything else outside the whitelist** → named entity if one exists (`&quot;`, `&amp;`, `&lt;`, `&gt;`), otherwise numeric reference (`&#xHH;` for ≤ 255, `&#xHHHH;` for BMP, full hex for supplementary plane).

## Signature

```php
public function escHtmlAttr(string $str): string;
```

Or via the facade:

```php
Esc::esc(string $str, 'attr', ?string $encoding = null): string;
```

### Exceptions

| Throws                          | When                                                          |
| ------------------------------- | ------------------------------------------------------------- |
| `InvalidUtf8Exception`          | `$str` is not valid UTF-8 (after any encoding conversion).    |
| `EncodingConversionException`   | iconv / mbstring fail during UTF-8 conversion.                |

Both extend [`EscaperException`](Exceptions.md).

## Examples

### Plain ASCII passes through

```php
Esc::esc('plain', 'attr');     // plain
Esc::esc('abc,XYZ.-_0123', 'attr');  // abc,XYZ.-_0123
```

### Space, `=`, parens, semicolon are rewritten

```php
Esc::esc('with space', 'attr');
// with&#x20;space

Esc::esc('" or 1=1', 'attr');
// &quot;&#x20;or&#x20;1&#x3D;1

Esc::esc('faketitle onmouseover=alert(1);', 'attr');
// faketitle&#x20;onmouseover&#x3D;alert&#x28;1&#x29;&#x3B;
```

### Defeating an unquoted-attribute injection

```php
$untrusted = 'faketitle onmouseover=alert(1);';

echo '<span title=' . Esc::esc($untrusted, 'attr') . '>hello</span>';
// <span title=faketitle&#x20;onmouseover&#x3D;alert&#x28;1&#x29;&#x3B;>hello</span>
```

The browser sees a single attribute value (the encoded space ends the value parse), not an extra `onmouseover` handler.

### Named entities are preferred

The four characters in the named-entity map use the shorter form:

```php
Esc::esc('"', 'attr');  // &quot;
Esc::esc('&', 'attr');  // &amp;
Esc::esc('<', 'attr');  // &lt;
Esc::esc('>', 'attr');  // &gt;
```

### Multibyte characters

```php
Esc::esc('ş', 'attr');  // &#x015F;
Esc::esc('🚀', 'attr'); // &#x1F680;
```

### Control characters

```php
Esc::esc("\x00", 'attr');  // &#xFFFD;
Esc::esc("\x1B", 'attr');  // &#xFFFD;
Esc::esc("\x7F", 'attr');  // &#xFFFD;
Esc::esc("\xC2\x80", 'attr');  // &#xFFFD;   ← U+0080 in proper UTF-8
Esc::esc("\xC2\x9F", 'attr');  // &#xFFFD;   ← U+009F in proper UTF-8
```

Tab / LF / CR are explicitly exempted (they are valid in HTML):

```php
Esc::esc("\t", 'attr');  // &#x09;
Esc::esc("\n", 'attr');  // &#x0A;
Esc::esc("\r", 'attr');  // &#x0D;
```

`U+00A0` (NO-BREAK SPACE) sits one code point above the C1 range and is escaped as a normal character, not replaced:

```php
Esc::esc("\xC2\xA0", 'attr');  // &#xA0;
```

### Empty and digit-only input short-circuits

```php
Esc::esc('', 'attr');       // ''
Esc::esc('12345', 'attr');  // 12345
```

Those inputs are already attribute-safe; the matcher is skipped entirely.

## When **not** to use it

| Location                              | Why                                                                                                  | Use instead                                |
| ------------------------------------- | ---------------------------------------------------------------------------------------------------- | ------------------------------------------ |
| `href=""`, `src=""`, `action=""`       | `escHtmlAttr` only protects the attribute *delimiters*. `javascript:` survives.                       | [`escUrl`](Context-URL.md) + scheme whitelist |
| `style="..."`                          | The attribute is safe, but its **content** is CSS.                                                    | [`escCss`](Context-CSS.md) for the inner value|
| `onclick="..."` and other `on*`        | The attribute is safe, but its **content** is JavaScript.                                             | [`escJs`](Context-JavaScript.md) for the inner|
| Bare `<script>` body                   | Wrong context entirely — entities aren't decoded in scripts.                                          | [`escJs`](Context-JavaScript.md)              |

## Why every value goes through UTF-8 internally

Even when the configured output encoding is, say, `windows-1252`, the matcher needs to address individual code points to apply the C0/C1 control-replacement and named-entity rules. The pipeline is:

```
[input in $encoding]
        ↓ iconv/mbstring
[input in UTF-8]
        ↓ preg_replace_callback with the matcher
[escaped in UTF-8]
        ↓ iconv/mbstring
[output in $encoding]
```

If iconv/mbstring fails at either end, `EncodingConversionException` is raised. If the converted input is not well-formed UTF-8, `InvalidUtf8Exception` is raised. See [Encodings](Encodings.md) and [Exceptions](Exceptions.md).

## See also

- [HTML body context](Context-HTML.md)
- [JavaScript context](Context-JavaScript.md) — for inline event handlers
- [URL context](Context-URL.md) — for href/src
- [Security Notes](Security-Notes.md)
- [API Reference → `escHtmlAttr`](API-Reference.md#eschtmlattr)
