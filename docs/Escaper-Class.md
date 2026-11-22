# `Escaper` Class

```php
use InitPHP\Escaper\Escaper;
```

`InitPHP\Escaper\Escaper` is the object behind the [`Esc`](Esc-Facade.md) facade. Each instance is bound to one output encoding and exposes one method per context.

Use it directly when:

- You inject the escaper into a view layer / template engine and want one configured instance per render pipeline.
- You build a library that takes an `Escaper` so the consumer controls lifecycle.
- You want strict object lifecycle without the static memoisation that the facade implies.

For ad-hoc `<?= ?>` use in views, the [`Esc`](Esc-Facade.md) facade is shorter.

## Constructor

```php
public function __construct(?string $encoding = null);
```

| Argument    | Behaviour                                                                                                       |
| ----------- | --------------------------------------------------------------------------------------------------------------- |
| `$encoding` | Lower-cased and compared against the supported whitelist. `null` and `''` resolve to `'utf-8'`. Anything else throws `EncodingNotSupportedException`. |

```php
new Escaper();                  // UTF-8
new Escaper(null);              // UTF-8
new Escaper('');                // UTF-8
new Escaper('UTF-8');           // UTF-8 (case-insensitive)
new Escaper('windows-1252');    // windows-1252

new Escaper('utf-16');
// throws: EncodingNotSupportedException — Encoding "utf-16" is not supported.
```

The full supported list lives in [Encodings](Encodings.md).

## Public methods

| Method                            | Context                  | Returns / throws                                          |
| --------------------------------- | ------------------------ | --------------------------------------------------------- |
| `getEncoding(): string`           | —                        | Lower-cased configured encoding.                          |
| `escHtml(string $s): string`      | HTML body                | Escaped string. Never throws.                             |
| `escHtmlAttr(string $s): string`  | HTML attribute           | Escaped string. May throw `InvalidUtf8Exception` / `EncodingConversionException`. |
| `escJs(string $s): string`        | JavaScript string literal| Escaped string. Same exceptions as `escHtmlAttr`.        |
| `escCss(string $s): string`       | CSS value                | Escaped string. Same exceptions.                          |
| `escUrl(string $s): string`       | URL component            | `rawurlencode($s)`. Never throws.                         |

Each context page has the full rule set:

- [HTML body context](Context-HTML.md)
- [HTML attribute context](Context-HTML-Attribute.md)
- [JavaScript context](Context-JavaScript.md)
- [CSS context](Context-CSS.md)
- [URL context](Context-URL.md)

## Common patterns

### Inject into a renderer

```php
final class ViewRenderer
{
    public function __construct(private Escaper $escaper) {}

    public function render(string $template, array $data): string
    {
        $safe = array_map(
            fn ($value) => is_string($value) ? $this->escaper->escHtml($value) : $value,
            $data
        );

        // ...substitute into $template...
    }
}

$renderer = new ViewRenderer(new Escaper());
```

The `Escaper` becomes an explicit dependency. Easy to swap for a fake in tests.

### One instance per encoding, kept in a container

```php
$container->set('escaper.utf8',    fn () => new Escaper());
$container->set('escaper.cp1252',  fn () => new Escaper('windows-1252'));
```

Each instance is independent — they don't share state, so concurrent use is fine.

### Querying the configured encoding

```php
$escaper = new Escaper('Windows-1252');
echo $escaper->getEncoding();  // windows-1252  (lower-cased)
```

`getEncoding()` returns the lower-cased form because the matcher and conversion paths use it as the canonical key.

## Failure modes

### Unsupported encoding

```php
new Escaper('utf-16');
// throws EncodingNotSupportedException
//        Encoding "utf-16" is not supported.
```

### Malformed UTF-8 input (attribute/JS/CSS contexts)

```php
(new Escaper())->escHtmlAttr("\xC3\x28");
// throws InvalidUtf8Exception
//        String to be escaped was not valid UTF-8 or could not be converted.
```

`escHtml()` does not raise this exception — it leans on `htmlspecialchars()` with `ENT_SUBSTITUTE`, which replaces malformed bytes with `U+FFFD`. The other three contexts insist on well-formed UTF-8 because the per-context matchers need to address full code points, not bytes.

### iconv / mbstring conversion failure

```php
$escaper = new Escaper('windows-1252');
$escaper->escHtml($bytesThatCannotBeRoundTripped);
// throws EncodingConversionException
//        Failed to convert string from "windows-1252" to "UTF-8".
```

The previous (1.x) behaviour was to silently substitute an empty string. 2.0 raises a typed exception instead. See the [Migration Guide](Migration-Guide.md#3-encoding-conversion-failure-now-throws-behavioural-break).

## Equivalence with `Esc::esc()`

These two snippets produce identical output for the same input:

```php
Esc::esc('<', 'attr', 'windows-1252');
(new Escaper('windows-1252'))->escHtmlAttr('<');
```

The facade is a thin dispatcher; it just memoises the underlying `Escaper`.

## See also

- [`Esc` facade](Esc-Facade.md) — the static dispatcher.
- [Encodings](Encodings.md) — supported encodings and conversion mechanics.
- [Exceptions](Exceptions.md) — exception hierarchy.
- [API Reference](API-Reference.md) — every method on one page.
