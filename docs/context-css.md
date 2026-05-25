# CSS context (`escCss`)

> Use when the value lands inside a CSS property value:
> `color: HERE;`, `background-image: url(HERE);`, `<style>div { color: HERE; }</style>`.

## What it does

`escCss` whitelists `[A-Za-z0-9]`. Every other character is rewritten as
the CSS escape sequence `\HEX `, with the **mandatory trailing space**
that terminates the escape.

The trailing space looks redundant when followed by another character,
but CSS uses it as a delimiter — without it, the parser would eat
hex-digit-looking characters that follow the escape. Always emit it.

## Example — preventing a `</style>` breakout

```php
use InitPHP\Escaper\Esc;

$untrusted = '</style><script>alert(1)</script>';

echo Esc::esc($untrusted, 'css');
// \3C \2F style\3E \3C script\3E alert\28 1\29 \3C \2F script\3E
```

The `<`, `>`, `/`, `(`, `)`, and spaces all turn into CSS escapes, so
the attacker cannot close the `<style>` block and inject a script.

## Multibyte characters

```php
echo Esc::esc('ş', 'css');
// \15F 

echo Esc::esc('🚀', 'css');
// \1F680 
```

Both come out as single CSS escape sequences regardless of how many
UTF-8 bytes they used in the input.

## When **not** to use it

- **CSS selectors built from user input** — `escCss` is for property
  *values*. Building a selector from untrusted data is dangerous even
  with escaping, because selectors can themselves trigger script in
  some legacy contexts (`expression()`, etc.). Don't.
- **CSS `url()` arguments that hold a real URL** — escape the URL with
  [`escUrl`](context-url.md) first; the result is then already safe to
  drop inside CSS as a plain string.
- **Inline `style` attribute** — the attribute value first needs to be
  HTML-attribute-safe, and the CSS inside it needs to be CSS-safe.
  Apply `escCss` to the property value, and rely on the attribute
  quoting that the templating engine adds.

## Empty / digit-only inputs

Returned untouched, as in the other contexts.
