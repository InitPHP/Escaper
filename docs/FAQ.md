# FAQ

## Is `htmlspecialchars()` not enough?

Not on its own. `htmlspecialchars()` is the right tool for the HTML **body** context (and in fact `escHtml()` is a thin wrapper around it). It is the **wrong** tool for HTML attributes, JavaScript blocks, CSS blocks and URLs.

This package gives you one helper per context so the rule you apply matches the place the data lands. See [Security Notes → Pick the right context](Security-Notes.md#2-pick-the-right-context) for the matrix of common mistakes.

## Should I use the `Esc` facade or the `Escaper` class?

Use **`Esc`** for:

- Templates (`<?= Esc::esc($v) ?>`).
- Controllers / view helpers in non-DI codebases.
- Recursive escaping of arrays / request payloads.

Use **`Escaper`** for:

- View-layer / renderer classes you'd otherwise hardcode the facade into.
- Libraries you ship to other developers.
- Anything you'd dependency-inject for tests.

The two are interchangeable in terms of output — see [Escaper Class → Equivalence with `Esc::esc()`](Escaper-Class.md#equivalence-with-escesc).

## Do I need to escape integers and booleans?

No. `Esc::esc()` passes non-string scalars and objects through unchanged:

```php
Esc::esc(42);         // 42
Esc::esc(true);       // true
Esc::esc(null);       // null
Esc::esc(new stdClass()); // same stdClass
```

This makes it safe to call on heterogeneous arrays without pre-filtering. The behaviour is documented in [Esc Facade → Return value by input type](Esc-Facade.md#return-value-by-input-type).

## Why is my JavaScript invalid after escaping?

`escJs()` produces a fragment safe inside a string literal — it does **not** add the surrounding quotes. The classic mistake:

```php
// Wrong:
<script>
    var greeting = <?= Esc::esc($value, 'js') ?>;
</script>

// Right:
<script>
    var greeting = "<?= Esc::esc($value, 'js') ?>";
</script>
```

The quotes are yours to add. See [JavaScript context → The caller supplies the quotes](Context-JavaScript.md#-the-caller-supplies-the-quotes).

## What's the difference between `escJs` and `json_encode`?

| Use                                                                | Pick                                                                                          |
| ------------------------------------------------------------------ | --------------------------------------------------------------------------------------------- |
| Embedding a single string inside an existing JS string literal      | `escJs($value)`                                                                               |
| Outputting a structured value (array, object, multi-typed payload)  | `json_encode($value, JSON_UNESCAPED_UNICODE \| JSON_HEX_TAG \| JSON_HEX_AMP \| JSON_HEX_APOS \| JSON_HEX_QUOT)` |
| Filling a `<script type="application/json">…</script>` block        | `json_encode(...)` as above                                                                   |

`escJs` is a **per-character escape**; `json_encode` is a **whole-value serialiser**. They solve adjacent but different problems.

## Why does `escCss` add a trailing space to every escape?

Because CSS requires it. The escape sequence is `\HEX` followed by a delimiter; without the trailing space, the CSS parser would absorb following hex-digit-looking characters into the same token:

```php
Esc::esc('!', 'css');   // \21    (space terminates the escape)
```

Without the trailing space, `\21f00` would parse as `U+21F00` instead of `!` followed by `f00`. See [CSS context](Context-CSS.md).

## Does this package require `ext-mbstring`?

Yes, since 2.0. `composer.json` lists it under `require`. `ext-iconv` is preferred when present (the conversion path tries iconv first) but is optional and listed under `suggest`. See [Installation](Installation.md).

## My input is ISO-8859-1 / Windows-1252 / Shift_JIS. Does this work?

Yes. Pass the encoding name to the constructor (or as the third argument to `Esc::esc()`):

```php
$escaper = new Escaper('windows-1252');
$escaper->escHtmlAttr($value);

// or:
Esc::esc($value, 'attr', 'windows-1252');
```

The escaper converts to UTF-8 internally, runs the per-context matcher, then converts back. The full supported list is in [Encodings](Encodings.md).

## Why did my code throw an exception in 2.0 that worked in 1.x?

Two changes are likely:

1. **Encoding-conversion failure** now raises `EncodingConversionException` instead of silently returning an empty string.
2. **Malformed UTF-8** in attribute/JS/CSS contexts now raises `InvalidUtf8Exception` (1.x did the same for some inputs but the error path was different).

Both are documented in the [Migration Guide](Migration-Guide.md).

## How do I escape the same value for two contexts at once?

You don't — you nest the calls **outermost context last**. For an inline event handler:

```php
$value = $untrusted;
$jsValue = $escaper->escJs($value);                   // 1. JS string literal
$attr    = $escaper->escHtmlAttr("alert('{$jsValue}')"); // 2. HTML attribute

echo "<button onclick=\"{$attr}\">click</button>";
```

The HTML parser sees the attribute first, so HTML escaping wraps the JS-escaped value, not the other way around. Same logic applies to URL-in-attribute (`escUrl` then `escHtmlAttr`).

## Is `escUrl` the right tool for a whole URL?

No. `escUrl` percent-encodes a single URL **component**. Pass it a whole URL and every `:`, `/`, `?`, `&` gets escaped — the URL stops being a URL. See [URL context → What it does not do](Context-URL.md#what-it-does-not-do).

## How does the `Esc` facade cache work?

Per-encoding. A `private static array $instances` keyed by lower-cased encoding name; the first call for a given encoding constructs an `Escaper` and stores it. Subsequent calls reuse the same instance. `null` and `''` are normalised to `'utf-8'`.

Under Swoole / RoadRunner / Octane / FrankenPHP the cache survives across requests — the cached `Escaper` instances are stateless, so this is normally fine. Call `Esc::reset()` if you need a hard reset. See [Esc Facade → How memoisation works](Esc-Facade.md#how-memoisation-works).

## Where is the changelog?

In the repository: [`CHANGELOG.md`](https://github.com/InitPHP/Escaper/blob/main/CHANGELOG.md). The 2.0 entry lists every change in this release.

## Where do I report a security issue?

Through the disclosure process in the org-wide [`SECURITY.md`](https://github.com/InitPHP/.github/blob/main/SECURITY.md). Please do not open a public GitHub issue for security-sensitive reports.

## I think the docs are wrong / unclear.

Please open an issue at [github.com/InitPHP/Escaper/issues](https://github.com/InitPHP/Escaper/issues). Documentation fixes are reviewed eagerly.
