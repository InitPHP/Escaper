# Upgrading from 1.x to 2.0

`initphp/escaper` 2.0 is a correctness release. The public API surface
is unchanged — every 1.x method still exists with the same signature.
What changed is **how the escaper signals failure** and **what happens
in a few edge cases that were latent bugs in 1.x**.

If your 1.x code only calls `Esc::esc()` or `Escaper::escHtml()` etc.
in the happy path, you should be able to upgrade without code changes.
The notes below cover the cases where you may need to act.

## 1. New `composer require`: `ext-mbstring`

`composer.json` now declares `ext-mbstring` as a hard requirement
(`ext-iconv` remains optional but is preferred when present). If your
production image does not bundle mbstring you must add it:

```Dockerfile
RUN docker-php-ext-install mbstring
```

Or on a Debian/Ubuntu host:

```bash
apt-get install -y php-mbstring
```

## 2. Replace `catch (\Exception $e)` blocks (recommended)

1.x threw a plain `\Exception`. 2.x ships a dedicated exception tree.
Your existing `\Exception` (or `\Throwable`) catches still work because
the new exceptions extend `\RuntimeException`, but you can now be
specific:

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

The full tree:

```
\RuntimeException
    └─ InitPHP\Escaper\Exception\EscaperException
        ├─ EncodingNotSupportedException     // unsupported encoding constructor arg
        ├─ EncodingConversionException       // iconv/mbstring failure (NEW behaviour, see §3)
        ├─ InvalidContextException           // unknown context passed to Esc::esc()
        └─ InvalidUtf8Exception               // input is not / cannot be UTF-8
```

## 3. Encoding-conversion failure now throws (behavioural break)

In 1.x, if `iconv` / `mb_convert_encoding` returned `false`, the
escaper silently substituted an empty string and returned it. That
silently destroyed data. 2.x raises `EncodingConversionException`
instead.

If you rely on the old "empty string on failure" behaviour, add an
explicit `try`/`catch`:

```php
try {
    $safe = $escaper->escHtmlAttr($value);
} catch (EncodingConversionException $e) {
    $safe = '';
}
```

Most callers will want the exception. If you were silently corrupting
output before, you will now see the error.

## 4. `Esc::esc()` on arrays now keeps the `$encoding` argument

This is a bug fix. In 1.x, `Esc::esc(['x' => $v], 'html',
'iso-8859-1')` recursed into the array and called itself **without
the encoding**, so every nested value escaped as UTF-8 regardless of
the third argument. 2.x propagates the encoding correctly.

If you were depending on the bug (i.e. you passed an encoding but
expected UTF-8 for nested values), drop the encoding argument:

```diff
-Esc::esc($payload, 'html', 'iso-8859-1');
+Esc::esc($payload, 'html');
```

## 5. C1 control characters in multibyte UTF-8

`escHtmlAttr` always replaced single-byte C0/C1 controls with the
Unicode replacement character (`U+FFFD`). In 1.x the replacement only
fired against the **first byte** of a multibyte sequence, so
`U+0080`–`U+009F` in their proper 2-byte UTF-8 form (`\xC2\x80` …
`\xC2\x9F`) survived as numeric character references (`&#x80;` …
`&#x9F;`) instead of being replaced.

2.x catches both forms. The output for those exact code points
changed from `&#x80;` etc. to `&#xFFFD;`. Both are XSS-safe; if you
were diffing output byte-for-byte across versions, expect this drift.

## 6. `Esc::esc()` cache is now actually effective

Not a BC break, but worth knowing: in 1.x the static cache rebuilt the
`Escaper` on every call when `$encoding === null`. 2.x caches per
encoding. No code change needed — your default-encoding calls just got
faster.

## 7. Examples directory removed

The runnable PHP files under `Examples/` are gone. The same scenarios
live under [`docs/`](./docs) with each output verified by running the
escaper itself. If you scripted against the example file paths, point
your tooling at `docs/` instead.

## 8. Static analysis & coding-standard tooling (dev only)

If you have a fork or downstream patches, note that 2.x adds:

- `phpstan.neon.dist` (level `max`, zero errors)
- `.php-cs-fixer.dist.php` (`@PSR12 + @PHP74Migration`)

Your local changes should pass `composer ci` before being submitted as
PRs.

## Summary checklist

- [ ] `ext-mbstring` available in every environment.
- [ ] `catch (\Exception)` → `catch (EscaperException)` (optional).
- [ ] Handle `EncodingConversionException` if you used to rely on the
      silent empty-string fallback.
- [ ] Drop redundant `$encoding` arguments that depended on the
      recursion bug.
- [ ] Re-run any byte-for-byte output snapshots that include the
      `U+0080`–`U+009F` range.
