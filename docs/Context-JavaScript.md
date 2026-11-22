# JavaScript context (`escJs`)

> Use when the value lands **inside a JavaScript string literal**:
> `<script>var x = "HERE";</script>`, `<button onclick="alert('HERE')">`.

## What it does

`escJs()` whitelists `[A-Za-z0-9,._]`. Every other character is rewritten as a JavaScript escape sequence:

| Input form                                          | Output form                |
| --------------------------------------------------- | -------------------------- |
| Single-byte character (`U+0000`–`U+007F`)           | `\xNN` (upper-case hex)    |
| BMP multibyte character (`U+0080`–`U+FFFF`)         | `\uNNNN` (upper-case hex)  |
| Above the BMP (`U+10000`–`U+10FFFF`, e.g. emoji)    | UTF-16 surrogate pair: `\uHHHH\uHHHH` |

The whitelist explicitly *includes* `,`, `.`, `_` because those are safe in JS string contents and would otherwise generate noisy escape sequences.

## ⚠ The caller supplies the quotes

`escJs()` produces a **fragment** that is safe inside a string literal. It does **not** add the surrounding quotes — you do. The standard usage is:

```php
<script>
    var greeting = "<?= Esc::esc($value, 'js') ?>";
</script>
```

Forgetting the quotes produces invalid JavaScript (not a security issue, but a silent breakage). The dedicated FAQ entry on this gotcha lives at [FAQ → Why is my JavaScript invalid after escaping?](FAQ.md#why-is-my-javascript-invalid-after-escaping).

## Signature

```php
public function escJs(string $str): string;
```

Or via the facade:

```php
Esc::esc(string $str, 'js', ?string $encoding = null): string;
```

### Exceptions

| Throws                          | When                                                          |
| ------------------------------- | ------------------------------------------------------------- |
| `InvalidUtf8Exception`          | `$str` is not valid UTF-8 (after any encoding conversion).    |
| `EncodingConversionException`   | iconv / mbstring fail during UTF-8 conversion.                |

## Examples

### Plain ASCII passes through

```php
Esc::esc('plain', 'js');                 // plain
Esc::esc('abc,XYZ._0', 'js');            // abc,XYZ._0
```

### Single-byte special characters

```php
Esc::esc(' ',  'js');   // \x20
Esc::esc('"',  'js');   // \x22
Esc::esc('/',  'js');   // \x2F
Esc::esc('<',  'js');   // \x3C
Esc::esc('\\', 'js');   // \x5C
```

### A real injection vector

```php
$untrusted = '"; alert(1); var x="';

Esc::esc($untrusted, 'js');
// \x22\x3B\x20alert\x281\x29\x3B\x20var\x20x\x3D\x22
```

Pasted inside a template:

```php
<script>
    var foo = "<?= Esc::esc($untrusted, 'js') ?>";
</script>
```

Browser sees one continuous string literal — the attacker's `";` cannot break out.

### BMP multibyte → `\uXXXX`

```php
Esc::esc('ş', 'js');         // ş
Esc::esc('Türkçe', 'js');    // Türkçe
```

### Supplementary plane → surrogate pair

```php
Esc::esc('🚀', 'js');        // 🚀
```

This is correct UTF-16 surrogate-pair encoding (high `D83D`, low `DE80`) for `U+1F680` ROCKET. JavaScript engines decode it back to the single code point.

### `</script>` breakout vector

```php
Esc::esc('</script>', 'js');  // \x3C\x2Fscript\x3E
```

The `<` and `/` become hex escapes, so even if the attacker tries to inject a closing tag the resulting JS source still parses as a string.

### Empty / digit-only short-circuits

```php
Esc::esc('', 'js');       // ''
Esc::esc('98765', 'js');  // 98765
```

## When **not** to use it

| Location                                   | Why                                                                                                              | Use instead                       |
| ------------------------------------------ | ---------------------------------------------------------------------------------------------------------------- | --------------------------------- |
| JSON output                                | JSON has its own well-defined serialisation; `escJs` is for inline embedding inside a string literal.            | `json_encode($value, JSON_UNESCAPED_UNICODE \| JSON_HEX_TAG \| JSON_HEX_AMP \| JSON_HEX_APOS \| JSON_HEX_QUOT)` |
| `<script type="application/json">…</script>`| That block is JSON, not inline JavaScript.                                                                       | `json_encode(...)` as above       |
| Building an identifier / keyword / property name | `escJs` is designed for **values**. Don't paste user input into a variable name or a property accessor.       | Hardcode the identifier or use a fixed lookup table |
| The middle of a template literal (backticks)| `escJs` does not escape `${...}` interpolation syntax.                                                            | Use a regular string literal `""` |
| Inline event handler attribute (`onclick=""`)| You need **both** JS escaping (inside the handler) and HTML attribute escaping (around it).                       | `escJs` then `escHtmlAttr` on the result |

## Why every value goes through UTF-8 internally

Same as the [attribute context](Context-HTML-Attribute.md#why-every-value-goes-through-utf-8-internally) — the matcher needs to address full code points, which requires well-formed UTF-8. See [Encodings](Encodings.md).

## See also

- [HTML attribute context](Context-HTML-Attribute.md) — for the surrounding `on*` attribute
- [Security Notes](Security-Notes.md)
- [FAQ → JSON vs. JS escaping](FAQ.md#whats-the-difference-between-escjs-and-json_encode)
- [API Reference → `escJs`](API-Reference.md#escjs)
