# Changelog

All notable changes to `initphp/escaper` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0]

A reliability- and correctness-focused major release. Several bug fixes
in this release are visible from the outside, so the version bump is
necessary even though the public surface is unchanged. See
[`UPGRADE-2.0.md`](./UPGRADE-2.0.md) for a step-by-step migration guide.

### Added

- Dedicated exception hierarchy under `InitPHP\Escaper\Exception\`:
  `EscaperException` (base, extends `\RuntimeException`),
  `EncodingNotSupportedException`, `EncodingConversionException`,
  `InvalidContextException`, `InvalidUtf8Exception`.
- Full PHPUnit test suite under `tests/` (62 tests, 100 assertions,
  ~91% line coverage).
- GitHub Actions CI: `composer validate`, PHP-CS-Fixer, PHPStan (max),
  PHPUnit on PHP 7.4 – 8.4, coverage upload.
- PHPStan configuration at the `max` level with zero reported issues.
- PHP-CS-Fixer configuration based on `@PSR12` and
  `@PHP74Migration` rule sets.
- Developer documentation under `docs/` covering each escape context,
  encoding handling, exceptions and security notes.
- `Esc::reset()` helper to clear the memoised `Escaper` cache (used by
  tests; useful when the calling code wants to drop cached instances).
- `composer.json` scripts: `test`, `test-coverage`, `stan`, `cs-check`,
  `cs-fix`, `ci`.

### Changed

- **`Esc::esc()` recursion** now propagates `$encoding` into recursive
  calls. Previously the encoding was dropped on every inner call,
  silently defaulting to UTF-8 for nested arrays.
- **`Esc::esc()` instance cache** is now keyed by encoding. The previous
  cache compared `$escaper->getEncoding()` (`'utf-8'`) against the raw
  `$encoding` argument (often `null`), so the cache rebuilt on every
  default call.
- **`Escaper` constructor** raises `EncodingNotSupportedException`
  instead of `\Exception`. (Still catchable via `\Exception` /
  `\RuntimeException`.)
- **`HTML attribute` matcher** evaluates the C0/C1 control-character
  check against the decoded code point, so `U+0080`–`U+009F` are now
  correctly replaced with `U+FFFD` when they arrive in multibyte UTF-8
  form. Previously only single-byte controls were caught.
- **`composer.json`** now requires `ext-mbstring`. `ext-iconv` remains
  optional and is preferred when present (`suggest` entry added).
- **PHPDoc blocks** rewritten across the package to reflect the actual
  code behaviour.

### Fixed

- **Silent data loss on encoding-conversion failure.** When `iconv` /
  `mb_convert_encoding` returned `false`, `Escaper::convertEncoding()`
  previously returned an empty string and let it propagate, masking
  real failures. It now raises `EncodingConversionException`.
- **`isUtf8()`** uses explicit `=== 1` comparison against
  `preg_match()` instead of relying on PHP's loose type coercion in a
  `bool` return.
- **Misleading error message** in `convertEncoding()`: the "MB_String
  plugin is required" text appeared even when iconv was tried first.
  Replaced with "Either ext-iconv or ext-mbstring is required".
- **Unused callable properties** (`$htmlAttrMatcher`, `$jsMatcher`,
  `$cssMatcher`) removed. The matchers are now passed inline to
  `preg_replace_callback`.

### Removed

- **`Examples/`** directory removed. The same scenarios are documented
  under [`docs/`](./docs) with verified output for each example.

## [1.0]

Initial release.

[Unreleased]: https://github.com/InitPHP/Escaper/compare/2.0.0...HEAD
[2.0.0]: https://github.com/InitPHP/Escaper/compare/1.0...2.0.0
[1.0]: https://github.com/InitPHP/Escaper/releases/tag/1.0
