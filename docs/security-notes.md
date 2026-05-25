# Security notes

Output escaping is necessary but **not sufficient** for safe rendering.
This page collects the things the per-context guides only hint at.

## 1. Escape on output, not on input

It is tempting to escape data once, at the boundary where it enters
the system, and store the escaped form in the database. Do not do
this:

- The "escape" is context-dependent — HTML-escaping a value at input
  time makes it wrong for JSON, CSV, JavaScript, plain-text email,
  search indexing, …
- Once stored as escaped text, you lose the original value and have to
  un-escape it for every non-HTML use, which inevitably leaks an
  unescaped path.

Store the original bytes. Escape them at the place — and in the
context — they are about to be rendered.

## 2. Pick the right context

`htmlspecialchars()` is **not** enough on its own. Each of the five
contexts has different rules, and applying the wrong one can leave the
output exploitable:

| Output location           | Wrong escaper                    | What attacker can do |
| ------------------------- | -------------------------------- | -------------------- |
| Unquoted attribute        | `escHtml`                        | inject `onmouseover=…` via a space |
| `<script>` body           | `escHtml`                        | HTML entities are not decoded in scripts; payload survives intact |
| `<style>` body            | `escHtml`                        | same problem as `<script>` |
| `href`/`src`              | `escHtml` only                   | `javascript:` scheme runs code |
| Inline event handler      | `escHtmlAttr` only               | the attribute is safe but its JS contents are not |

The matrix in [getting started](getting-started.md#picking-a-context)
shows the right pairing for each location.

## 3. URL scheme validation

The URL escaper does not (and cannot) prevent `javascript:` or
`data:` URLs from running. If the URL itself comes from user input,
validate the scheme against a whitelist **before** escaping:

```php
$url = $userSuppliedUrl;
$scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');
if (!in_array($scheme, ['http', 'https', 'mailto'], true)) {
    $url = '#';
}

echo '<a href="' . Esc::esc($url, 'attr') . '">link</a>';
```

## 4. Content-Security-Policy is your seatbelt

Even with perfect escaping, a CSP header narrows the blast radius of
any escaping bug you may still have. A minimal, strict policy is a
strong second line of defence; a permissive `unsafe-inline` policy
undoes a lot of what escaping bought you. Treat them together, not as
substitutes.

## 5. Avoid building code from user input

The escapers protect *values* embedded in a fixed code template. They
do **not** protect against building executable code from user input —
do not concatenate untrusted text into a JavaScript identifier, a CSS
selector, a SQL fragment, a shell command, or a regular expression.
Use placeholders / prepared APIs in each of those contexts.

## 6. Authoritative references

When in doubt about a specific edge case, defer to:

- [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [WHATWG HTML — § Restrictions on content models](https://html.spec.whatwg.org/multipage/syntax.html)
- [RFC 3986 — Uniform Resource Identifier](https://www.rfc-editor.org/rfc/rfc3986)
- [CSS Syntax Module Level 3 — § Escapes](https://drafts.csswg.org/css-syntax-3/#escape-diagram)

If you discover an escaping bug in this package, please report it
through the process in
[SECURITY.md](https://github.com/InitPHP/.github/blob/main/SECURITY.md)
rather than opening a public issue.
