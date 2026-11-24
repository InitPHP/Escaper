# Security Notes

Output escaping is necessary but **not sufficient** for safe rendering. This page collects the things the per-context pages only hint at, and gives you the reasoning behind the rules.

## 1. Escape on output, not on input

It is tempting to escape data once, at the boundary where it enters the system, and store the escaped form in the database. **Don't.**

- The "escape" is context-dependent. HTML-escaping a value at input time makes it wrong for JSON, CSV, JavaScript, plain-text email, full-text search indexing, …
- Once stored as escaped text, you lose the original value and have to un-escape it for every non-HTML use, which inevitably leaks an unescaped path.
- Re-escaping the same value compounds. `Tom & Jerry` becomes `Tom &amp; Jerry` becomes `Tom &amp;amp; Jerry` (see [Context-HTML → Double-escaping is not detected](Context-HTML.md#double-escaping-is-not-detected)).

**Rule:** store original bytes. Escape them at the place — and in the context — they are about to be rendered.

## 2. Pick the right context

`htmlspecialchars()` is **not** enough on its own. Each of the five contexts has different rules, and applying the wrong one can leave the output exploitable:

| Output location              | Wrong escaper                | What an attacker can do                                                                |
| ---------------------------- | ---------------------------- | -------------------------------------------------------------------------------------- |
| Unquoted attribute           | `escHtml`                    | A literal space ends the attribute value — inject `onmouseover=…`.                      |
| `<script>` body              | `escHtml`                    | HTML entities are not decoded inside scripts — the payload survives intact.             |
| `<style>` body               | `escHtml`                    | Same problem — CSS does not decode HTML entities.                                       |
| `href` / `src`               | `escHtml` only               | `escHtml` doesn't stop `javascript:` schemes. URL encoding + scheme validation does.    |
| Inline event handler         | `escHtmlAttr` only           | The attribute parser is happy but the handler's JS contents are not escaped.            |
| `<style>` `expression(...)`  | `escCss` alone               | Legacy IE quirk — never let user data drive CSS function names; only values.             |

The matrix in [Quick Start → Pick the right context](Quick-Start.md#2-pick-the-right-context) shows the correct pairing for each location.

## 3. URL scheme validation

The URL escaper does not — and cannot — prevent `javascript:`, `data:`, `vbscript:`, or other code-running schemes from being honoured by the browser. If the URL itself comes from user input, validate the scheme against a whitelist **before** escaping:

```php
$url    = $userSuppliedUrl;
$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

if (!in_array($scheme, ['http', 'https', 'mailto'], true)) {
    $url = '#';
}

echo '<a href="' . Esc::esc($url, 'attr') . '">link</a>';
```

A common variant is to allow only site-relative URLs. Do **not** build this from a deny-list such as "`parse_url` found no scheme and it does not start with `//`": `parse_url()` does not recognise a scheme that contains a tab or newline (`java<TAB>script:…`), and browsers strip those characters before parsing the URL, so the value runs as `javascript:`. `/\evil.com` also passes such a check and is treated by browsers as a protocol-relative URL. Match the whole value against an allow-list pattern instead:

```php
// Accept only an absolute path ("/account/orders?page=2"): it must start with a
// single "/", and contain no control characters, spaces or backslashes.
if (preg_match('#^/(?![/\\\\])[^\x00-\x20\\\\]*$#D', $url) !== 1) {
    $url = '#';
}

echo '<a href="' . Esc::esc($url, 'attr') . '">link</a>';
```

Anything that does not match — including a bare `page.html`, which this pattern rejects on purpose — becomes `#`. Whichever variant you use, escaping happens last and does not replace the validation: `Esc::esc($url, 'attr')` keeps tab, newline and carriage return as character references (`&#x09;`), which the browser decodes back before it parses the URL.

## 4. Content-Security-Policy is your seatbelt

Even with perfect escaping, a CSP header narrows the blast radius of any escaping bug you may still have. A minimal, strict policy:

```
Content-Security-Policy:
    default-src 'self';
    script-src 'self';
    style-src 'self';
    img-src 'self' data:;
    object-src 'none';
    base-uri 'self';
    frame-ancestors 'none';
```

A permissive `unsafe-inline` policy undoes a lot of what escaping bought you. Treat CSP and escaping together, not as substitutes.

## 5. Avoid building code from user input

The escapers protect **values** embedded in a fixed code template. They do **not** protect against building executable code from user input — do not concatenate untrusted text into:

- A JavaScript identifier or keyword.
- A CSS selector.
- A SQL fragment.
- A shell command.
- A regular-expression pattern.
- An HTML tag name or attribute name.

Use placeholders / prepared APIs in each of those contexts: PDO bound parameters, `preg_quote()` for regex, `escapeshellarg()` for shell, etc.

## 6. Don't trust `Content-Type` to save you

Setting `Content-Type: text/html; charset=utf-8` is necessary — without it the browser may sniff the document and interpret your "JSON" or "plain text" output as HTML, undoing every escape decision you made. But it is **not sufficient**: even with the header set correctly, a `<script>` tag inside an HTML body will still run.

Always combine:

1. A correct, explicit `Content-Type` header (`charset=utf-8`).
2. `X-Content-Type-Options: nosniff` to disable sniffing.
3. Context-appropriate output escaping per this package.

## 7. Be deliberate about `raw` and the empty context

`Esc::esc($value, 'raw')` and `Esc::esc($value, '')` return the input unchanged. They exist so callers can pass a context name through a template variable without special-casing "no escaping". The cost is that **typos look like opting out**:

```php
Esc::esc($value, 'htm');    // throws InvalidContextException — correct
Esc::esc($value, 'raw');    // returns $value unchanged — by design
Esc::esc($value, ' raw ');  // throws InvalidContextException
                            // — whitespace is not stripped before lookup
```

If you find yourself using `'raw'` regularly, write the un-escaped flow as direct echo of `$value` — it's more obviously dangerous and harder to overlook in a code review.

## 8. Long-running PHP (Swoole / RoadRunner / Octane / FrankenPHP)

The [`Esc`](Esc-Facade.md) facade caches `Escaper` instances in a `private static array`. Under long-running PHP that cache survives across requests. The escaper instances are **stateless** — they hold only the configured encoding — so this is normally fine and gives you the performance benefit.

If you want a hard reset at request boundaries (you almost certainly don't, but if you do):

```php
\InitPHP\Escaper\Esc::reset();
```

## 9. Authoritative references

When in doubt about a specific edge case, defer to:

- [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html) — the rules this package implements.
- [OWASP Cross Site Scripting Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/DOM_based_XSS_Prevention_Cheat_Sheet.html) — DOM-side XSS, which output escaping alone cannot address.
- [WHATWG HTML — § Restrictions on content models](https://html.spec.whatwg.org/multipage/syntax.html) — the canonical HTML parsing rules.
- [RFC 3986 — Uniform Resource Identifier](https://www.rfc-editor.org/rfc/rfc3986) — what `escUrl` implements.
- [CSS Syntax Module Level 3 — § Escapes](https://drafts.csswg.org/css-syntax-3/#escape-diagram) — the `\HEX ` rule `escCss` follows.

## Reporting a vulnerability

If you discover an escaping bug in this package, please report it through the disclosure process in [SECURITY.md](https://github.com/InitPHP/.github/blob/main/SECURITY.md) rather than opening a public issue.
