# URL context (`escUrl`)

> Use when the value lands **inside a single URL component** — most often a query-string parameter:
> `<a href="/search?q=HERE">`, `https://example.com/?name=HERE&page=HERE`.

## What it does

`escUrl()` is a thin wrapper around PHP's built-in `rawurlencode()`. The output follows RFC 3986: every character outside `[A-Za-z0-9\-._~]` is percent-encoded.

Unlike `urlencode()` (which encodes spaces as `+` and is form-specific), `rawurlencode()` produces `%20` for a space, which is correct in URL **paths and query values**.

## Signature

```php
public function escUrl(string $str): string;
```

Or via the facade:

```php
Esc::esc(string $str, 'url', ?string $encoding = null): string;
```

`escUrl()` does not throw. The `$encoding` argument is accepted for facade-shape consistency but has no effect — `rawurlencode()` is byte-oriented and produces percent-encoded ASCII regardless.

## Examples

### Spaces and reserved characters

```php
Esc::esc('hello world', 'url');     // hello%20world
Esc::esc('name=value', 'url');      // name%3Dvalue
Esc::esc('a&b=c', 'url');           // a%26b%3Dc
```

### RFC 3986 unreserved characters pass through

```php
Esc::esc('Hello.world-1_2~3', 'url');  // Hello.world-1_2~3
```

### Path separators are encoded too

```php
Esc::esc('/path/to/file.html', 'url');
// %2Fpath%2Fto%2Ffile.html
```

This is correct for a single component but wrong for a whole path — see "What it does not do" below.

### A real injection vector

```php
$query = '" onmouseover="alert(1)';

echo '<a href="https://example.com/?q=' . Esc::esc($query, 'url') . '">link</a>';
// <a href="https://example.com/?q=%22%20onmouseover%3D%22alert%281%29">link</a>
```

### Multibyte input

```php
Esc::esc('türkçe', 'url');  // t%C3%BCrk%C3%A7e
Esc::esc('🚀', 'url');       // %F0%9F%9A%80
```

UTF-8 bytes are percent-encoded one at a time, which is the RFC 3986 expectation.

## What it does **not** do

`escUrl` encodes a single URL **component**, not a whole URL. Passing an entire URL through it produces a useless string:

```php
Esc::esc('https://example.com/?q=foo', 'url');
// https%3A%2F%2Fexample.com%2F%3Fq%3Dfoo   ← every : / ? = got eaten
```

The correct pattern is to construct the URL from trusted parts and escape only the untrusted components:

### One untrusted query value

```php
$base = 'https://example.com/search';

echo '<a href="' . $base . '?q=' . Esc::esc($userQuery, 'url') . '">link</a>';
```

### Multiple untrusted query values — use `http_build_query`

```php
$qs = http_build_query([
    'q'    => $userQuery,
    'sort' => $userSort,
]);

// http_build_query already percent-encodes, so just escape the attribute wrapper:
echo '<a href="' . Esc::esc("$base?$qs", 'attr') . '">link</a>';
```

### Untrusted path segments

```php
$slug = Esc::esc($userSlug, 'url');
echo '<a href="/articles/' . $slug . '">read</a>';
```

## URL scheme validation — `escUrl` does not do this

If the entire URL is user-supplied, encoding alone will not stop a `javascript:` or `data:` URL from running. Validate the scheme against a whitelist **before** escaping:

```php
$url    = $userSuppliedUrl;
$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

if (!in_array($scheme, ['http', 'https', 'mailto'], true)) {
    $url = '#';
}

echo '<a href="' . Esc::esc($url, 'attr') . '">link</a>';
```

Keep this an **allow-list** of known-good schemes. A check that only rejects "has a scheme" is not enough, because `parse_url()` does not detect `java<TAB>script:`, which browsers still run as `javascript:`. For a site-relative-only variant, see [Security Notes → URL scheme validation](Security-Notes.md#3-url-scheme-validation).

See [Security Notes → URL scheme validation](Security-Notes.md#3-url-scheme-validation).

## When **not** to use it

| Location                          | Why                                                                                       | Use instead                                |
| --------------------------------- | ----------------------------------------------------------------------------------------- | ------------------------------------------ |
| Whole URLs                        | Every `:`, `/`, `?`, `&` gets percent-encoded; the URL stops being a URL.                  | Encode only the dynamic components.        |
| Form `application/x-www-form-urlencoded` where the consumer expects `+` for space | Some legacy systems require `+` rather than `%20`.                    | `urlencode()` directly.                    |
| `<a href="…">` outer attribute    | The URL component is already safe — but the surrounding attribute needs HTML escaping.    | `escUrl` for the component, [`escHtmlAttr`](Context-HTML-Attribute.md) (or rely on quoting) for the wrapper. |
| URL fragment after `#`            | Same RFC 3986 rules as a query value, so `escUrl` works here too. **Do** use it.           | (use `escUrl`)                             |

## Empty / digit-only input

`rawurlencode('')` returns `''` and digit-only input is unchanged, so the wrapper is effectively a no-op for those:

```php
Esc::esc('', 'url');     // ''
Esc::esc('12345', 'url'); // 12345
```

## See also

- [HTML attribute context](Context-HTML-Attribute.md) — for the `href="…"` wrapper
- [Security Notes → URL scheme validation](Security-Notes.md#3-url-scheme-validation)
- [API Reference → `escUrl`](API-Reference.md#escurl)
