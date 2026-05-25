# URL context (`escUrl`)

> Use when the value lands inside a URL component — most often a query
> string parameter:
> `<a href="/search?q=HERE">`, `https://example.com/?name=HERE`.

## What it does

`escUrl` is a thin wrapper around PHP's `rawurlencode()`. The output
follows RFC 3986: every character outside `A-Z a-z 0-9 - _ . ~` is
percent-encoded.

Unlike `urlencode()`, the result encodes a literal space as `%20`,
which is correct in URL **paths and query values** and in the
`application/x-www-form-urlencoded` payload.

## Example

```php
use InitPHP\Escaper\Esc;

$untrusted = '" onmouseover="alert(1)';

echo Esc::esc($untrusted, 'url');
// %22%20onmouseover%3D%22alert%281%29
```

```php
echo Esc::esc('foo bar', 'url');
// foo%20bar

echo Esc::esc('Hello.world-1_2~3', 'url');
// Hello.world-1_2~3
```

## What it does **not** do

`escUrl` encodes a single URL **component**, not a whole URL. Do not
pass an entire URL through it — every `:`, `/`, `?` and `&` would be
escaped and the result would be a useless string.

The correct pattern is:

```php
$base  = 'https://example.com/search';
$query = http_build_query([
    'q'    => $userQuery,   // http_build_query already encodes
    'sort' => $userSort,
]);

echo '<a href="' . Esc::esc($base . '?' . $query, 'attr') . '">link</a>';
```

For one-off cases where you build the query string by hand:

```php
echo '<a href="https://example.com/?q=' . Esc::esc($userQuery, 'url') . '">link</a>';
```

## When **not** to use it

- **Schemes** — never let untrusted data choose the URL scheme
  (`javascript:`, `data:`, `vbscript:` etc. can run code). If your
  application accepts a user-supplied URL, validate the scheme against
  a whitelist (`https`, `http`, `mailto`, …) *before* you escape.
- **Whole URLs** — see above.
- **URL fragments containing `+`** — `escUrl` produces `%20` for a
  space, which is correct in modern URL parsing. Some legacy systems
  expect `+` instead; in that case use `urlencode()` directly.

## Empty / digit-only inputs

`rawurlencode()` returns an empty string for an empty input and leaves
digit-only inputs untouched, so the wrapper is a no-op for those.
