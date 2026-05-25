# HTML attribute context (`escHtmlAttr`)

> Use when the value lands inside an HTML attribute:
> `<span title="HERE">`, `<a href="HERE">`, `<input value=HERE>`.

## What it does

`escHtmlAttr` is strict: anything outside `[A-Za-z0-9,.\-_]` is
rewritten as an HTML entity. That whitelist is small enough to be safe
in **quoted, single-quoted and unquoted** attribute values.

- A handful of code points use named entities (`&quot;`, `&amp;`,
  `&lt;`, `&gt;`) when they have one.
- Everything else uses numeric character references (`&#xHH;` or
  `&#xHHHH;`).
- `U+0000`–`U+001F` (except tab, LF, CR) and `U+007F`–`U+009F` are
  replaced with `U+FFFD` because they cannot appear inside an HTML
  document.

## Example — defeating an unquoted-attribute injection

```php
use InitPHP\Escaper\Esc;

$untrusted = 'faketitle onmouseover=alert(1);';

echo '<span title=' . Esc::esc($untrusted, 'attr') . '>hello</span>';
// <span title=faketitle&#x20;onmouseover&#x3D;alert&#x28;1&#x29;&#x3B;>hello</span>
```

The space, `=`, parentheses and semicolon all become entity references,
so the browser sees one attribute value rather than the attacker's
extra `onmouseover` handler.

## Examples — multibyte and Unicode

```php
echo Esc::esc('ş', 'attr');
// &#x015F;

echo Esc::esc('🚀', 'attr');
// &#x1F680;
```

## When **not** to use it

- **URL attributes** (`href`, `src`, `action`) — `escHtmlAttr` only
  protects the attribute *delimiters*. Run the URL through
  [`escUrl`](context-url.md) first.
- **Event handlers** (`onclick`, `onfocus`, …) — those are JavaScript
  contexts, not HTML attribute contexts. Apply
  [`escJs`](context-javascript.md) to the JS code, then `escHtmlAttr`
  the result if it sits inside an `on*=…` attribute.
- **`style` attribute** — that is a CSS context. Use
  [`escCss`](context-css.md) inside `style="..."`.

## Empty / digit-only inputs

The escaper short-circuits when the input is empty or contains nothing
but ASCII digits — those values are already attribute-safe:

```php
Esc::esc('', 'attr');     // ''
Esc::esc('12345', 'attr'); // '12345'
```
