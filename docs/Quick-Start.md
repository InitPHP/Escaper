# Quick Start

A 5-minute tour. Assumes the package is installed — see [Installation](Installation.md) if not.

## 1. The two entry points

There are exactly two ways to reach the escaper.

### The `Esc` static facade — for everyday use

```php
use InitPHP\Escaper\Esc;

echo Esc::esc($value);                  // HTML body (default)
echo Esc::esc($value, 'attr');          // HTML attribute
echo Esc::esc($value, 'js');            // JavaScript string literal
echo Esc::esc($value, 'css');           // CSS value
echo Esc::esc($value, 'url');           // URL component
echo Esc::esc($value, 'raw');           // pass-through, no escaping
```

The facade is a one-liner over an internally memoised `Escaper`. See [Esc Facade](Esc-Facade.md) for the rules around encoding caching and arrays.

### The `Escaper` class — when you want a configured instance

```php
use InitPHP\Escaper\Escaper;

$escaper = new Escaper();           // UTF-8 (default)
$escaper = new Escaper('windows-1252');

$escaper->escHtml($value);
$escaper->escHtmlAttr($value);
$escaper->escJs($value);
$escaper->escCss($value);
$escaper->escUrl($value);
```

Inject an `Escaper` into your view layer when you want each render path to know its own encoding. See [Escaper Class](Escaper-Class.md).

## 2. Pick the right context

This is the single most important thing to get right. Each context has different rules.

```php
$untrusted = '<script>alert(1)</script>';

echo Esc::esc($untrusted, 'html');
// &lt;script&gt;alert(1)&lt;/script&gt;

echo Esc::esc($untrusted, 'attr');
// &lt;script&gt;alert&#x28;1&#x29;&lt;&#x2F;script&gt;

echo Esc::esc($untrusted, 'js');
// \x3Cscript\x3Ealert\x281\x29\x3C\x2Fscript\x3E

echo Esc::esc($untrusted, 'css');
// \3C script\3E alert\28 1\29 \3C \2F script\3E

echo Esc::esc($untrusted, 'url');
// %3Cscript%3Ealert%281%29%3C%2Fscript%3E
```

The rule of thumb:

| If the value goes into…              | Use         |
| ------------------------------------ | ----------- |
| `<p>HERE</p>` / `<div>HERE</div>`    | `'html'`    |
| `<input value="HERE">` / `title=HERE`| `'attr'`    |
| `<script>var x = "HERE";</script>`   | `'js'`      |
| `<style>color: HERE;</style>`        | `'css'`     |
| `<a href="?q=HERE">`                 | `'url'`     |

Mixing them up reintroduces XSS — see [Security Notes](Security-Notes.md) for the matrix of common mistakes.

## 3. Escape a whole structure in one call

`Esc::esc()` recurses into arrays and leaves non-string scalars alone:

```php
$payload = [
    'title'    => '<b>hi</b>',
    'meta'     => ['x' => '<i>'],
    'votes'    => 42,
    'nullable' => null,
];

print_r(Esc::esc($payload, 'html'));
// Array
// (
//     [title]    => &lt;b&gt;hi&lt;/b&gt;
//     [meta]     => Array ( [x] => &lt;i&gt; )
//     [votes]    => 42
//     [nullable] =>
// )
```

Useful at the view boundary when you trust the structure but not the content.

## 4. Don't double-escape

The escaper does not know whether your input is already escaped — it always escapes. Calling it twice produces double-escaped output:

```php
echo Esc::esc('Tom & Jerry');                    // Tom &amp; Jerry
echo Esc::esc(Esc::esc('Tom & Jerry'));          // Tom &amp;amp; Jerry — wrong!
```

Rule: **store raw, escape on output, never on input.** See [Security Notes](Security-Notes.md#1-escape-on-output-not-on-input) for the full argument.

## 5. Handle failure

Every escaper exception extends `InitPHP\Escaper\Exception\EscaperException`. A single `catch` covers them all:

```php
use InitPHP\Escaper\Esc;
use InitPHP\Escaper\Exception\EscaperException;

try {
    echo Esc::esc($value, 'attr', 'iso-8859-1');
} catch (EscaperException $e) {
    error_log($e->getMessage());
    echo '[escape failed]';
}
```

See [Exceptions](Exceptions.md) for the tree and what triggers each member.

## 6. Use a non-default encoding

```php
$escaper = new Escaper('windows-1252');

$escaper->escHtml("\xE9");
// "\xE9" — input is treated as windows-1252 and the output stays in that encoding.
```

The full list of recognised names lives in [Encodings](Encodings.md).

## What next

You've seen the surface. Pick the page that matches your task:

- **Context deep dives** — [HTML body](Context-HTML.md), [HTML attribute](Context-HTML-Attribute.md), [JavaScript](Context-JavaScript.md), [CSS](Context-CSS.md), [URL](Context-URL.md).
- **Choosing between `Esc` and `Escaper`** — [Esc Facade](Esc-Facade.md), [Escaper Class](Escaper-Class.md).
- **Reference** — [API Reference](API-Reference.md).
- **Common questions** — [FAQ](FAQ.md).
