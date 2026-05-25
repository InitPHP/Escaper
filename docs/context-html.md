# HTML body context (`escHtml`)

> Use when the value lands between HTML tags:
> `<p>HERE</p>`, `<div>HERE</div>`, `<li>HERE</li>`.

## What it does

`escHtml` is a thin wrapper around `htmlspecialchars()` configured with
`ENT_QUOTES | ENT_SUBSTITUTE`:

- `ENT_QUOTES` escapes both single and double quotes, so the same
  output is safe inside an attribute should it ever be moved.
- `ENT_SUBSTITUTE` replaces malformed UTF-8 with `U+FFFD` instead of
  returning an empty string — failing safe, not silently.

## Example

```php
use InitPHP\Escaper\Esc;

echo Esc::esc('<script>alert("xss")</script>');
// &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;

echo Esc::esc("Tom & Jerry's adventure");
// Tom &amp; Jerry&#039;s adventure
```

The escaper does not touch characters that are already safe in the HTML
body context — letters, digits, accented characters, emoji, all pass
through:

```php
echo Esc::esc('Merhaba dünya 🚀');
// Merhaba dünya 🚀
```

## When **not** to use it

- **Attribute values** — `escHtml` is safe enough for quoted attributes
  but loses to unquoted attributes (a space ends the value). Use
  [`escHtmlAttr`](context-html-attribute.md) instead.
- **`<script>` blocks** — HTML entities are not decoded inside a
  script. Use [`escJs`](context-javascript.md).
- **`<style>` blocks** — same problem. Use
  [`escCss`](context-css.md).
- **URLs in `href` / `src`** — escape the URL with
  [`escUrl`](context-url.md) first, then put the escaped URL through
  `escHtml` if needed.
