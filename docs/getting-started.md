# Getting started

## Install

```bash
composer require initphp/escaper
```

The package needs PHP 7.4 or newer, plus `ext-ctype` and `ext-mbstring`.
`ext-iconv` is optional but used in preference to mbstring when present.

## The two entry points

There are two ways to reach the escaper.

### 1. The `Esc` static facade

The fastest way to escape one value:

```php
use InitPHP\Escaper\Esc;

$safe = Esc::esc($value, 'html');
```

`Esc::esc()` accepts a string or an array (recursed into), dispatches to
the right method on a memoised `Escaper` instance, and returns the
escaped value. Use it when you do not want to think about lifecycle.

### 2. The `Escaper` object

Useful when you want one instance bound to a specific encoding, or you
want to inject the escaper into your view layer:

```php
use InitPHP\Escaper\Escaper;

$escaper = new Escaper();           // UTF-8
$escaper = new Escaper('windows-1252');

$escaper->escHtml($string);
$escaper->escHtmlAttr($string);
$escaper->escJs($string);
$escaper->escCss($string);
$escaper->escUrl($string);
```

The two paths produce identical output for the same input/encoding —
`Esc` is simply a thin wrapper.

## Picking a context

| Where does the value go? | Use            | Context name |
| ------------------------ | -------------- | ------------ |
| Between HTML tags        | `escHtml`      | `html`       |
| Inside an HTML attribute | `escHtmlAttr`  | `attr`       |
| Inside a JS string       | `escJs`        | `js`         |
| Inside a CSS value       | `escCss`       | `css`        |
| Inside a URL component   | `escUrl`       | `url`        |

If you mix these up you can re-introduce XSS even after "escaping".
Each per-context guide in this directory spells the rules out in detail.

## Escaping a whole structure

`Esc::esc()` recurses into arrays. Non-string, non-array values inside
the array are returned untouched:

```php
$payload = ['title' => '<b>hi</b>', 'votes' => 42];

Esc::esc($payload);
// ['title' => '&lt;b&gt;hi&lt;/b&gt;', 'votes' => 42]
```

A top-level value that is neither a string nor an array is returned
unchanged.
