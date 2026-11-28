# InitPHP Escaper — Documentation

Welcome to the official documentation for **`initphp/escaper`** — a small, dependency-free, context-aware output escaper for PHP 8.2+. It implements the rules from the [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html) for the five most common output contexts.

```bash
composer require initphp/escaper
```

```php
use InitPHP\Escaper\Esc;

echo Esc::esc('<script>alert(1)</script>');
// &lt;script&gt;alert(1)&lt;/script&gt;
```

`htmlspecialchars()` alone is not safe in every context. Pasting a "safely escaped" value into an unquoted attribute, a `<script>` block, a `<style>` block, or an `href` will reintroduce XSS even when you *think* you have escaped it. This package gives you one helper per context, so the rule you apply matches the place the data lands.

## At a glance — five contexts, five helpers

| Where the value lands                | Method                                 | Documentation page                         |
| ------------------------------------ | -------------------------------------- | ------------------------------------------ |
| Between HTML tags                    | `escHtml($s)` — context `'html'`       | [HTML body context](Context-HTML.md)          |
| Inside an HTML attribute             | `escHtmlAttr($s)` — context `'attr'`   | [HTML attribute context](Context-HTML-Attribute.md) |
| Inside a JavaScript string literal   | `escJs($s)` — context `'js'`           | [JavaScript context](Context-JavaScript.md)   |
| Inside a CSS property value          | `escCss($s)` — context `'css'`         | [CSS context](Context-CSS.md)                 |
| Inside a URL component               | `escUrl($s)` — context `'url'`         | [URL context](Context-URL.md)                 |

There are two entry points: a tiny static facade ([`Esc`](Esc-Facade.md)) for one-liners, and the underlying object ([`Escaper`](Escaper-Class.md)) when you want to keep an encoded instance around.

## Start here

- **New?** Read [Installation](Installation.md), then [Quick Start](Quick-Start.md).
- **Just want the recipe for one context?** Jump straight to the relevant context page above.
- **Choosing between the facade and the class?** See [`Esc` facade](Esc-Facade.md) and [`Escaper` class](Escaper-Class.md).
- **Working with non-UTF-8 input?** Read [Encodings](Encodings.md).
- **Need to handle failures?** See [Exceptions](Exceptions.md).
- **Going to production?** Read [Security Notes](Security-Notes.md).
- **Want every method documented?** [API Reference](API-Reference.md) is exhaustive.
- **Coming from 1.x?** Read the [Migration Guide](Migration-Guide.md).
- **Stuck on something specific?** The [FAQ](FAQ.md) might have it.

## Why pick this one

- **Five purpose-built escapers.** One per OWASP context. Same naming, same encoding handling, same exception tree.
- **Verified output.** The PHPUnit suite locks the exact byte-for-byte output of every escape rule, including multibyte UTF-8, BMP-overflow code points, control-character handling and the named-entity table.
- **Strict failure mode.** Encoding-conversion failures raise a typed exception instead of returning an empty string. No silent data loss.
- **PHPStan `level: max`, zero issues.** Hard to regress without CI catching it.
- **Zero runtime dependencies.** `ext-ctype` and `ext-mbstring` are the entire footprint. `ext-iconv` is optional and is preferred when available.
- **Tested on PHP 8.2 → 8.6** in CI on every push, with a `--prefer-lowest` dependency run on PHP 8.2 and 8.4.

## At a glance — feature matrix

| Capability                                                      | Status |
| --------------------------------------------------------------- | :----: |
| `escHtml()` — HTML body context                                 | ✅ |
| `escHtmlAttr()` — quoted, single-quoted **and** unquoted attrs  | ✅ |
| `escJs()` — JavaScript string literal                           | ✅ |
| `escCss()` — CSS value                                          | ✅ |
| `escUrl()` — RFC 3986 percent-encoding                          | ✅ |
| Recursive escape over arrays (`Esc::esc($payload)`)             | ✅ |
| Named-entity preference over numeric refs (`&quot;`, `&amp;`, …) | ✅ |
| Multibyte UTF-8 input (Turkish, Greek, CJK, …)                  | ✅ |
| BMP-overflow code points (emoji, surrogate pairs in JS)         | ✅ |
| C0/C1 control-character replacement (`U+FFFD`)                  | ✅ |
| Non-UTF-8 input/output encodings (iso-8859-x, windows-12xx, …) | ✅ |
| Typed exception hierarchy                                       | ✅ |
| `Esc` static facade with per-encoding memoisation               | ✅ |

## Conventions used in this documentation

- Code samples assume the autoloader has already been required.
- Output shown in `// comments` is the literal string the escaper returns.
- "Untrusted" means any data that has touched the network, the filesystem, a database, or anything else outside your PHP process — in other words, "almost everything".

## Package metadata

- **License**: MIT
- **Minimum PHP**: 8.2 (tested on 8.2, 8.3, 8.4, 8.5, 8.6 in CI)
- **Runtime dependencies**: `ext-ctype`, `ext-mbstring`
- **Optional**: `ext-iconv` (preferred when present)
- **Packagist**: [`initphp/escaper`](https://packagist.org/packages/initphp/escaper)
- **Source**: [github.com/InitPHP/Escaper](https://github.com/InitPHP/Escaper)
- **Issues**: [github.com/InitPHP/Escaper/issues](https://github.com/InitPHP/Escaper/issues)
- **Discussions**: [github.com/orgs/InitPHP/discussions](https://github.com/orgs/InitPHP/discussions)
- **Security**: [`SECURITY.md`](https://github.com/InitPHP/.github/blob/main/SECURITY.md)

If something in this documentation is unclear, ambiguous, or wrong, please [open an issue](https://github.com/InitPHP/Escaper/issues/new) — documentation fixes are reviewed eagerly.
