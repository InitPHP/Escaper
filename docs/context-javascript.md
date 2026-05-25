# JavaScript context (`escJs`)

> Use when the value lands inside a JavaScript string literal:
> `var foo = "HERE";`, `<script>var foo = 'HERE';</script>`.

## What it does

`escJs` whitelists `[A-Za-z0-9,._]`. Every other character is rewritten
as a JavaScript escape sequence:

- Single-byte characters → `\xNN`.
- BMP multibyte characters → `\uNNNN`.
- Characters above the BMP (e.g. emoji) → a UTF-16 surrogate pair
  `\uHHHH\uHHHH`.

Both single-byte and multibyte cases output upper-case hex.

## Important — the caller adds the quotes

`escJs` produces a *fragment* that is safe **inside** a string literal.
It does **not** add the surrounding quotes. The standard usage is:

```php
use InitPHP\Escaper\Esc; ?>

<script>
    var greeting = "<?= Esc::esc($value, 'js') ?>";
</script>
```

Forgetting the quotes will produce invalid JavaScript, not a security
issue — but it is the single most common mistake with JS escapers.

## Examples

```php
use InitPHP\Escaper\Esc;

echo Esc::esc('"; alert(1); var x="', 'js');
// \x22\x3B\x20alert\x281\x29\x3B\x20var\x20x\x3D\x22

echo Esc::esc('ş', 'js');
// \u015F

echo Esc::esc('🚀', 'js');
// \uD83D\uDE80
```

## When **not** to use it

- **JSON output** — use `json_encode($value, JSON_UNESCAPED_UNICODE |
  JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)`
  instead. JSON is its own well-defined serialisation; `escJs` is for
  inline embedding inside a string literal.
- **JavaScript identifiers, keywords or object keys** — `escJs` is
  designed for **values**, not code. Do not paste user input into a
  variable name or a property accessor.
- **`<script type="application/json">` blocks** — those are JSON, not
  inline JavaScript; the JSON rules above apply.

## Empty / digit-only inputs

Like the other contexts, empty strings and ASCII-digit-only strings are
returned untouched.
