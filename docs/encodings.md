# Encodings

The escaper works internally in UTF-8 but can read and write a fixed
list of legacy single-byte and double-byte encodings.

## Default

`new Escaper()` (or any `Esc::esc()` call without an `$encoding`
argument) operates in UTF-8 end to end. No conversion is performed and
no extra extension is needed beyond `ext-ctype` and `ext-mbstring`.

## Supported encodings

The constructor lower-cases the requested encoding and checks it
against a whitelist:

```
iso-8859-1     iso8859-1
iso-8859-5     iso8859-5
iso-8859-15    iso8859-15
utf-8
cp866          ibm866          866
cp1251         windows-1251    win-1251    1251
cp1252         windows-1252    1252
koi8-r         koi8-ru         koi8r
big5           950
gb2312         936
big5-hkscs
shift_jis      sjis            sjis-win    cp932    932
euc-jp         eucjp           eucjp-win
macroman
```

Anything outside this list raises `EncodingNotSupportedException`.

## How conversion works

When the configured encoding is not UTF-8:

1. The input string is converted to UTF-8 before the per-context
   matcher runs.
2. The converted string is validated — if it is not well-formed UTF-8,
   `InvalidUtf8Exception` is raised.
3. The escaped result is converted back to the configured encoding
   before it is returned.

The conversion itself uses `iconv()` when the extension is loaded, and
falls back to `mb_convert_encoding()` otherwise. If neither is
available the call raises `EncodingConversionException`. A failure
inside `iconv`/`mbstring` also raises `EncodingConversionException`
rather than silently returning an empty string.

## Example

```php
use InitPHP\Escaper\Escaper;

$escaper = new Escaper('iso-8859-1');

// Input is ISO-8859-1: 0xE9 is "é".
$output = $escaper->escHtml("\xE9");
// 0xE9 — htmlspecialchars left it alone, and the output stayed in ISO-8859-1.
```

## Choosing an encoding

Unless you have an external constraint (a legacy database, a fixed
output transport), prefer UTF-8 everywhere — it is the only encoding
where the escaper performs no conversion and no validation step can
fail with an `InvalidUtf8Exception`.
