# Encodings

The escaper works in UTF-8 internally and converts to/from the configured encoding at the edges. UTF-8 input is the default and the only case where no conversion happens.

## Default — UTF-8

```php
new Escaper();          // UTF-8
new Escaper(null);      // UTF-8
new Escaper('');        // UTF-8
new Escaper('UTF-8');   // UTF-8 (case-insensitive)
```

The constructor lower-cases its argument before lookup, so all four constructors above produce an identical instance.

## Supported encodings

```
iso-8859-1       iso8859-1
iso-8859-5       iso8859-5
iso-8859-15      iso8859-15
utf-8
cp866            ibm866           866
cp1251           windows-1251     win-1251     1251
cp1252           windows-1252     1252
koi8-r           koi8-ru          koi8r
big5             950
gb2312           936
big5-hkscs
shift_jis        sjis             sjis-win     cp932    932
euc-jp           eucjp            eucjp-win
macroman
```

Anything outside this list raises `EncodingNotSupportedException`:

```php
new Escaper('utf-16');
// EncodingNotSupportedException: Encoding "utf-16" is not supported.
```

## Conversion pipeline

When the configured encoding is *not* UTF-8, the escaper performs three steps for the attribute / JS / CSS contexts:

```
[input in $encoding]
        ↓ convertEncoding($from = $encoding, $to = 'UTF-8')
[input in UTF-8]
        ↓ preg_replace_callback with the matcher
[escaped result in UTF-8]
        ↓ convertEncoding($from = 'UTF-8', $to = $encoding)
[output in $encoding]
```

For `escHtml()` no UTF-8 round-trip is needed — `htmlspecialchars()` is called directly with the configured encoding. For `escUrl()` the input is treated as a byte stream and `$encoding` has no effect (`rawurlencode()` is byte-oriented).

### Which backend is used

```php
if (function_exists('iconv')) {
    iconv($from, $to, $str);
} elseif (function_exists('mb_convert_encoding')) {
    mb_convert_encoding($str, $to, $from);
} else {
    throw new EncodingConversionException(
        'Either ext-iconv or ext-mbstring is required to convert string encodings.'
    );
}
```

iconv is preferred when both are present. `composer.json` requires `ext-mbstring` so the fallback always works; `ext-iconv` is in `suggest`.

### Failure mode

If iconv/mbstring returns `false`, the escaper raises `EncodingConversionException`:

```php
// EncodingConversionException:
// Failed to convert string from "<from>" to "<to>".
```

In 1.x the same situation silently substituted an empty string. The 2.0 behaviour is strict — see the [Migration Guide](Migration-Guide.md#3-encoding-conversion-failure-now-throws-behavioural-break).

## Worked example — ISO-8859-1 round-trip

```php
use InitPHP\Escaper\Escaper;

$escaper = new Escaper('iso-8859-1');

// ISO-8859-1 0xE9 is "é".
$output = $escaper->escHtml("\xE9");

bin2hex($output);  // "e9"   — left alone; the output stayed in ISO-8859-1
```

For the attribute context the conversion does a full round-trip:

```php
$escaper = new Escaper('iso-8859-1');

$escaper->escHtmlAttr("\xE9");
// "&#xE9;"   — the matcher saw "é" (U+00E9) in UTF-8 and re-encoded back.
```

## UTF-8 validity check

After conversion, the escaper validates the result with the equivalent of:

```php
preg_match('/^./su', $str) === 1
```

This is cheaper than a full code-point walk and rejects truncated / overlong / invalid byte sequences. A failure raises `InvalidUtf8Exception`:

```php
(new Escaper())->escHtmlAttr("\xC3\x28");
// InvalidUtf8Exception:
// String to be escaped was not valid UTF-8 or could not be converted.
```

`escHtml()` does not perform this check — it relies on `htmlspecialchars()` with `ENT_SUBSTITUTE`, which replaces malformed bytes with `U+FFFD`. The other three contexts insist on well-formed UTF-8 because their matchers address full code points, not bytes.

## Choosing an encoding

Unless you have an external constraint (a legacy database column type, a fixed transport charset), prefer UTF-8 everywhere. It is:

- The fastest path — no conversion calls.
- The safest path — no chance of `EncodingConversionException` from edge-case input.
- The most-supported path — every modern client and renderer speaks it natively.

When you must use a legacy encoding, prefer one of the `windows-*` or `iso-*` names from the supported list, and make sure `ext-iconv` is loaded — its conversion tables are broader and faster than mbstring's defaults.

## See also

- [`Escaper` class](Escaper-Class.md) — constructor argument behaviour
- [Exceptions](Exceptions.md) — full failure tree
- [API Reference → `getEncoding`](API-Reference.md#getencoding)
