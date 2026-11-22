# Migration Guide — 1.x → 2.0

`initphp/escaper` 2.0 is a correctness release. The public API surface is unchanged — every 1.x method still exists with the same signature. What changed is **how the escaper signals failure** and **what happens in a few edge cases that were latent bugs in 1.x**.

If your 1.x code only calls `Esc::esc()` or `Escaper::escHtml()` etc. on the happy path, you should be able to upgrade without code changes. The notes below cover the cases where you may need to act.

## TL;DR — what changed

| Area                                | 1.x                                                                   | 2.0                                                                                              |
| ----------------------------------- | --------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| Minimum PHP                         | 7.4                                                                   | 8.2 (raised after 2.0.0; see the [changelog](https://github.com/InitPHP/Escaper/blob/main/CHANGELOG.md)) |
| `ext-mbstring`                      | optional                                                              | **required**                                                                                     |
| Encoding-conversion failure         | silently returned `''`                                                | throws `EncodingConversionException`                                                              |
| Exception base                      | plain `\Exception`                                                    | typed `EscaperException` (extends `\RuntimeException`)                                            |
| `Esc::esc()` recursion + encoding   | dropped `$encoding` on recursive calls                                | propagates `$encoding` correctly                                                                  |
| `Esc::esc()` instance cache         | rebuilt on every default-encoding call                                | per-encoding memoisation                                                                          |
| C1 control replacement              | only single-byte forms                                                 | also multibyte UTF-8 form (`\xC2\x80` … `\xC2\x9F`)                                              |
| `Examples/` directory               | shipped                                                               | removed (replaced by [`docs/`](https://github.com/InitPHP/Escaper/tree/main/docs) and this documentation) |

## 1. New `composer require`: `ext-mbstring`

`composer.json` now declares `ext-mbstring` as a hard requirement (`ext-iconv` remains optional but preferred when present). If your production image does not bundle mbstring you must add it:

```Dockerfile
RUN docker-php-ext-install mbstring
```

On a Debian / Ubuntu host:

```bash
apt-get install -y php-mbstring
```

## 2. Replace `catch (\Exception $e)` blocks (recommended)

1.x threw a plain `\Exception`. 2.0 ships a dedicated exception tree under `InitPHP\Escaper\Exception\` (see [Exceptions](Exceptions.md)).

Your existing `\Exception` or `\Throwable` catches still work because the new exceptions extend `\RuntimeException`, but you can now be specific:

```diff
 use InitPHP\Escaper\Esc;
+use InitPHP\Escaper\Exception\EscaperException;

 try {
     echo Esc::esc($value, 'attr');
-} catch (\Exception $e) {
+} catch (EscaperException $e) {
     // …
 }
```

Granular catch for the most common case — log encoding/UTF-8 problems, re-throw programmer errors:

```php
use InitPHP\Escaper\Exception\{
    EncodingConversionException,
    EncodingNotSupportedException,
    InvalidContextException,
    InvalidUtf8Exception,
};

try {
    $safe = Esc::esc($value, $context, $encoding);
} catch (EncodingNotSupportedException | InvalidContextException $e) {
    throw $e;  // programmer error
} catch (InvalidUtf8Exception | EncodingConversionException $e) {
    error_log($e->getMessage());
    $safe = '[invalid value]';
}
```

## 3. Encoding-conversion failure now throws (behavioural break)

In 1.x, if `iconv` / `mb_convert_encoding` returned `false`, the escaper silently substituted an empty string and returned it. That silently destroyed data.

2.0 raises `EncodingConversionException` instead:

```php
// 1.x: returned ''
// 2.0:
(new Escaper('windows-1252'))->escHtmlAttr($problematicBytes);
// EncodingConversionException: Failed to convert string from "windows-1252" to "UTF-8".
```

If you genuinely relied on the old "empty string on failure" behaviour, add an explicit `try`/`catch`:

```php
use InitPHP\Escaper\Exception\EncodingConversionException;

try {
    $safe = $escaper->escHtmlAttr($value);
} catch (EncodingConversionException $e) {
    $safe = '';
}
```

Most callers will prefer the exception — if you were silently corrupting output before, you'll now see it.

## 4. `Esc::esc()` on arrays now keeps the `$encoding` argument

This is a bug fix. In 1.x:

```php
Esc::esc(['x' => $v], 'html', 'iso-8859-1');
// Recursed into the array and called itself WITHOUT the encoding.
// Every nested value escaped as UTF-8 regardless of the third argument.
```

2.0 propagates the encoding correctly:

```php
Esc::esc(['x' => $v], 'html', 'iso-8859-1');
// Nested values are also escaped with iso-8859-1.
```

If you were depending on the bug (you passed an encoding but expected UTF-8 for nested values), drop the encoding argument:

```diff
-Esc::esc($payload, 'html', 'iso-8859-1');
+Esc::esc($payload, 'html');
```

## 5. C1 control characters in multibyte UTF-8

`escHtmlAttr` always replaced single-byte C0/C1 controls with the Unicode replacement character `U+FFFD`. In 1.x the replacement only fired against the **first byte** of a multibyte sequence, so `U+0080`–`U+009F` in their proper 2-byte UTF-8 form (`\xC2\x80` … `\xC2\x9F`) survived as numeric character references (`&#x80;` … `&#x9F;`) instead of being replaced.

2.0 catches both forms:

```php
Esc::esc("\xC2\x80", 'attr');  // 2.0: &#xFFFD;
                               // 1.x: &#x80;
```

Both are XSS-safe; if you were diffing output byte-for-byte across versions, expect this drift for the exact `U+0080`–`U+009F` range.

## 6. `Esc::esc()` cache is now actually effective

Not a BC break, but worth knowing.

In 1.x the static cache rebuilt the `Escaper` on every call when `$encoding === null`, because it compared `$escaper->getEncoding()` (`'utf-8'`) to the raw `$encoding` argument (often `null`), and `'utf-8' !== null` is always true.

2.0 normalises `null` and `''` to `'utf-8'` before lookup and keys the cache per encoding. No code change needed — your default-encoding calls just got faster.

## 7. Examples directory removed

The runnable PHP files under `Examples/` are gone. The same scenarios live under [`docs/`](https://github.com/InitPHP/Escaper/tree/main/docs) inside the repo and in the per-context pages (see the per-context pages: [HTML](Context-HTML.md), [HTML attribute](Context-HTML-Attribute.md), [JavaScript](Context-JavaScript.md), [CSS](Context-CSS.md), [URL](Context-URL.md)).

If you scripted against the example file paths, point your tooling at `docs/` instead.

## 8. Coding-standard, static analysis, CI (informational)

If you have a fork or downstream patches, note that 2.0 adds:

- `phpstan.neon.dist` — level `max`, zero reported errors.
- `.php-cs-fixer.dist.php` — a PHP-CS-Fixer configuration (since moved to the shared PER-CS configuration).
- GitHub Actions CI — PHPUnit with `--prefer-lowest` and highest-dependency variants, plus PHP-CS-Fixer and PHPStan jobs.

Your local changes should pass `composer ci` before being submitted as PRs.

## Summary checklist

- [ ] `ext-mbstring` available in every environment.
- [ ] `catch (\Exception)` → `catch (EscaperException)` (optional).
- [ ] Handle `EncodingConversionException` if you used to rely on the silent empty-string fallback.
- [ ] Drop redundant `$encoding` arguments that depended on the recursion bug.
- [ ] Re-run any byte-for-byte output snapshots that include the `U+0080`–`U+009F` range.
- [ ] If you forked, run `composer ci` against your changes.

## See also

- [`CHANGELOG.md`](https://github.com/InitPHP/Escaper/blob/main/CHANGELOG.md) — terse Keep-a-Changelog log of every release.
- [`UPGRADE-2.0.md`](https://github.com/InitPHP/Escaper/blob/main/UPGRADE-2.0.md) — repo-side copy of this guide for code-reading without leaving GitHub.
- [Exceptions](Exceptions.md) — the new exception tree in full.
- [FAQ](FAQ.md) — common questions after upgrading.
