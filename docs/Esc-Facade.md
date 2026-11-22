# `Esc` Facade

```php
use InitPHP\Escaper\Esc;
```

`InitPHP\Escaper\Esc` is a static facade with a single public method (`esc`) that dispatches to a memoised [`Escaper`](Escaper-Class.md) instance. It is the right entry point for:

- Templates (`<?= Esc::esc($v) ?>`)
- One-liners in controllers/views
- Recursive escaping of arrays (e.g. JSON payloads, request input)

If you need to keep a configured `Escaper` around — typically because your view layer pins one encoding — use the underlying class directly. See [`Escaper`](Escaper-Class.md).

## Signature

```php
public static function esc(
    mixed $data,
    string $context = 'html',
    ?string $encoding = null
): mixed;
```

| Parameter   | Accepts                                                                                          |
| ----------- | ------------------------------------------------------------------------------------------------ |
| `$data`     | Any value. Strings get escaped; arrays are recursed; everything else is returned unchanged.      |
| `$context`  | One of `html` (default), `attr`, `js`, `css`, `url`, `raw`, or the empty string. Case-insensitive. |
| `$encoding` | A supported encoding name (see [Encodings](Encodings.md)). `null` resolves to UTF-8.                |

## Return value by input type

| Input type            | Returned value                                                                                                                |
| --------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| `string`              | Escaped string per `$context`.                                                                                                |
| `array`               | A new array with each element recursively escaped. Keys are not touched. Non-string, non-array elements are kept as-is.       |
| anything else         | Returned unchanged (no copy, no exception).                                                                                   |

## Exceptions

| Throws                              | When                                                                                          |
| ----------------------------------- | --------------------------------------------------------------------------------------------- |
| `InvalidContextException`           | `$context` is not one of the recognised names (after lower-casing).                            |
| `EncodingNotSupportedException`     | `$encoding` is set but not in the supported list (raised lazily by `Escaper` construction).    |
| `EncodingConversionException`       | iconv / mbstring fail at runtime.                                                              |
| `InvalidUtf8Exception`              | Input cannot be expressed as UTF-8 (raised by `escHtmlAttr`/`escJs`/`escCss`, not `escHtml`).   |

All four extend [`EscaperException`](Exceptions.md). A single `catch (EscaperException $e)` covers every failure.

## Examples

### Default `html` context

```php
echo Esc::esc('<b>hi</b>');
// &lt;b&gt;hi&lt;/b&gt;
```

### Switching contexts

```php
echo Esc::esc('<', 'html');  // &lt;
echo Esc::esc('<', 'attr');  // &lt;
echo Esc::esc('<', 'js');    // \x3C
echo Esc::esc('<', 'css');   // \3C
echo Esc::esc('<', 'url');   // %3C
```

### Recursive array

```php
$payload = [
    'title'    => '<b>hi</b>',
    'meta'     => ['x' => '<i>'],
    'votes'    => 42,
    'nullable' => null,
];

print_r(Esc::esc($payload));
// Array
// (
//     [title]    => &lt;b&gt;hi&lt;/b&gt;
//     [meta]     => Array ( [x] => &lt;i&gt; )
//     [votes]    => 42
//     [nullable] =>
// )
```

### `raw` and empty context return input unchanged

```php
echo Esc::esc('<b>raw</b>', 'raw');  // <b>raw</b>
echo Esc::esc('<b>raw</b>', '');     // <b>raw</b>
```

### Case-insensitive context lookup

```php
Esc::esc('<', 'HTML');  // identical to Esc::esc('<', 'html')
Esc::esc('<', 'Attr');  // identical to Esc::esc('<', 'attr')
```

### Non-string scalars and objects

```php
Esc::esc(42);             // 42
Esc::esc(3.14);           // 3.14
Esc::esc(true);           // true
Esc::esc(null);           // null
Esc::esc(new stdClass()); // same stdClass
```

This makes `Esc::esc()` safe to call on heterogeneous arrays without pre-filtering.

## How memoisation works

The facade keeps a `private static array $instances` keyed by lower-cased encoding name (`'utf-8'`, `'iso-8859-1'`, …). The first call for a given encoding constructs a new `Escaper` and stores it; every subsequent call with that encoding reuses the same instance.

```php
Esc::esc('a');                          // creates Escaper('utf-8')
Esc::esc('b');                          // reuses Escaper('utf-8')
Esc::esc('c', 'html', 'iso-8859-1');    // creates Escaper('iso-8859-1')
Esc::esc('d', 'html', 'iso-8859-1');    // reuses Escaper('iso-8859-1')
```

`null` and empty-string encodings are normalised to `'utf-8'` before lookup, so passing `null` and `'UTF-8'` and `'utf-8'` all hit the same cache slot.

### Clearing the cache

```php
Esc::reset();
```

Removes every memoised `Escaper`. Intended for tests and long-running PHP setups (Swoole / RoadRunner / Octane / FrankenPHP) where you want to drop cached state at request boundaries.

## When **not** to use the facade

- **Multiple concurrent encodings of the same context** — you can do this through the facade by passing different `$encoding` arguments (different cache slots), but if you find yourself ping-ponging between two encodings inside one render pass, instantiate the [`Escaper`](Escaper-Class.md) directly so the lifecycle is explicit.
- **Inside libraries you ship to other people** — pass an injected `Escaper` so consumers can wire it through their DI container. The facade is convenience, not API.

## See also

- [`Escaper` class](Escaper-Class.md) — the object behind the facade.
- [Encodings](Encodings.md) — supported encodings, how conversion works.
- [Exceptions](Exceptions.md) — exception hierarchy and catch examples.
- [Security Notes](Security-Notes.md) — picking the right context, avoiding double-escaping.
