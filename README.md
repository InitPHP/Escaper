# initphp/escaper

Context-aware output escaper for PHP. Safely render untrusted user input inside
HTML, HTML attributes, JavaScript, CSS and URLs.

[![Latest Stable Version](https://poser.pugx.org/initphp/escaper/v)](https://packagist.org/packages/initphp/escaper)
[![PHP Version Require](https://poser.pugx.org/initphp/escaper/require/php)](https://packagist.org/packages/initphp/escaper)
[![CI](https://github.com/InitPHP/Escaper/actions/workflows/ci.yml/badge.svg)](https://github.com/InitPHP/Escaper/actions/workflows/ci.yml)
[![License](https://poser.pugx.org/initphp/escaper/license)](https://packagist.org/packages/initphp/escaper)
[![Total Downloads](https://poser.pugx.org/initphp/escaper/downloads)](https://packagist.org/packages/initphp/escaper)

`htmlspecialchars()` is not enough on its own. Each output context — an HTML
body, an attribute, a JavaScript string literal, a CSS value, a URL parameter
— needs its own escaping rules, and using the wrong one can leave you exposed
to XSS even when you *think* you have escaped your data.

`initphp/escaper` implements the rules from the
[OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
for the five most common contexts, behind a small, dependency-free API.

## Installation

```bash
composer require initphp/escaper
```

### Requirements

- PHP 7.4 or newer
- `ext-ctype`
- `ext-mbstring` (required); `ext-iconv` is used when present and preferred
  over mbstring

## Quick start

```php
use InitPHP\Escaper\Esc;

echo Esc::esc('<script>alert(1)</script>');
// &lt;script&gt;alert(1)&lt;/script&gt;

echo Esc::esc('faketitle onmouseover=alert(1);', 'attr');
// faketitle&#x20;onmouseover&#x3D;alert&#x28;1&#x29;&#x3B;

echo Esc::esc('"; alert(1); var x="', 'js');
// \x22\x3B\x20alert\x281\x29\x3B\x20var\x20x\x3D\x22

echo Esc::esc('</style><script>alert(1)</script>', 'css');
// \3C \2F style\3E \3C script\3E alert\28 1\29 \3C \2F script\3E

echo Esc::esc('" onmouseover="alert(1)', 'url');
// %22%20onmouseover%3D%22alert%281%29
```

`Esc::esc()` also accepts arrays and recurses into them, so escaping a whole
request payload at the view boundary is a one-liner:

```php
$safe = Esc::esc($_GET, 'html');
```

## API

### `Esc::esc()`

```php
public static function esc(
    array|string $data,
    string $context = 'html',
    ?string $encoding = null
): array|string;
```

| Argument    | Description                                                                 |
| ----------- | --------------------------------------------------------------------------- |
| `$data`     | A string, or an array (which is escaped recursively).                       |
| `$context`  | `html`, `attr`, `js`, `css`, `url`, or `raw` (returns input unchanged).     |
| `$encoding` | Output encoding. `null` resolves to UTF-8. See [Encodings](docs/encodings.md). |

Throws `InitPHP\Escaper\Exception\InvalidContextException` for unknown contexts.

### `Escaper`

For lower-level use, instantiate `Escaper` directly. Each instance is bound to
one encoding and exposes one method per context:

```php
use InitPHP\Escaper\Escaper;

$escaper = new Escaper();          // utf-8
$escaper = new Escaper('windows-1252');

$escaper->escHtml($string);
$escaper->escHtmlAttr($string);
$escaper->escJs($string);
$escaper->escCss($string);
$escaper->escUrl($string);
```

## Documentation

The [`docs/`](docs/) directory contains a per-context walkthrough with
examples, do-and-don't guidance and security notes:

- [Getting started](docs/getting-started.md)
- [HTML body context](docs/context-html.md)
- [HTML attribute context](docs/context-html-attribute.md)
- [JavaScript context](docs/context-javascript.md)
- [CSS context](docs/context-css.md)
- [URL context](docs/context-url.md)
- [Encodings](docs/encodings.md)
- [Exceptions](docs/exceptions.md)
- [Security notes](docs/security-notes.md)

## A word of warning

> Output escaping prevents XSS but it is not a substitute for input validation,
> authentication, or authorisation. It is also context-sensitive: the
> JavaScript escaper assumes the caller wraps the result in quotes, the HTML
> attribute escaper assumes the value is used as a single attribute value, and
> so on. Read the per-context docs before mixing contexts.

## Contributing

Contributions are welcome. Please read the
[org-wide CONTRIBUTING guide](https://github.com/InitPHP/.github/blob/main/CONTRIBUTING.md)
for the workflow, coding standards and test expectations.

A typical loop is:

```bash
git clone https://github.com/InitPHP/Escaper.git
cd Escaper
composer install
composer ci          # cs-check + phpstan + phpunit
```

Individual steps are also available:

| Command            | What it does                                |
| ------------------ | ------------------------------------------- |
| `composer test`    | Run PHPUnit                                 |
| `composer stan`    | Run PHPStan (max level)                     |
| `composer cs-check`| Report PHP-CS-Fixer violations, no changes  |
| `composer cs-fix`  | Apply PHP-CS-Fixer changes                  |

## Security

If you discover a security issue, please follow the disclosure process
documented in [SECURITY.md](https://github.com/InitPHP/.github/blob/main/SECURITY.md)
rather than opening a public issue.

## License

Released under the [MIT License](./LICENSE). © InitPHP.
