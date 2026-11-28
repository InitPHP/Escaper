# Installation

## Requirements

| Component         | Minimum                  | Notes                                                                                                                                                                  |
| ----------------- | ------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| PHP               | **8.2**                  | Tested on 8.2, 8.3, 8.4, 8.5, 8.6 in CI.                                                                                                                                    |
| `ext-ctype`       | always                   | Used for the digit-only fast path in every context escaper.                                                                                                            |
| `ext-mbstring`    | always                   | Required for the encoding-conversion fallback when `ext-iconv` is not available. Composer enforces it.                                                                |
| `ext-iconv`       | optional but preferred   | If present, used in place of mbstring for `iconv()`-based conversion. Composer lists it under `suggest`.                                                              |

## Install via Composer

```bash
composer require initphp/escaper
```

The package is autoloaded under the `InitPHP\Escaper\` namespace; nothing else needs to be wired.

## Verifying the install

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use InitPHP\Escaper\Esc;

echo Esc::esc('<script>alert(1)</script>');
// &lt;script&gt;alert(1)&lt;/script&gt;
```

If the script prints `&lt;script&gt;alert(1)&lt;/script&gt;`, the autoload is wired correctly.

## Optional development dependencies

For local testing, static analysis and coding-standard checks, the project's own `composer.json` declares:

```json
"require-dev": {
    "phpunit/phpunit": "^9.6 || ^10.5 || ^11.5",
    "phpstan/phpstan": "^1.12 || ^2.1",
    "friendsofphp/php-cs-fixer": "^3.95"
}
```

You only need these if you're contributing to the package itself. The `composer.json` exposes them via these scripts:

| Script                | What it does                                                |
| --------------------- | ----------------------------------------------------------- |
| `composer test`       | Run the PHPUnit test suite.                                 |
| `composer test-coverage` | Run PHPUnit and write an HTML coverage report to `build/coverage`. |
| `composer stan`       | Run PHPStan at the configured (max) level.                  |
| `composer cs-check`   | Report PHP-CS-Fixer violations without modifying any file.  |
| `composer cs-fix`     | Apply PHP-CS-Fixer fixes in place.                          |
| `composer ci`         | Run `cs-check` + `stan` + `test` back-to-back.              |

## CI matrix

The package's GitHub Actions workflow runs on every push and pull request and executes:

- `composer validate --strict` (one job).
- PHP-CS-Fixer (one job).
- PHPStan at `level: max` (one job).
- PHPUnit across PHP 8.2, 8.3, 8.4, 8.5 and 8.6 with the highest dependency set, plus a `--prefer-lowest` run on PHP 8.2 and 8.4.
- A coverage job that uploads to Codecov.

The workflow file lives at [`.github/workflows/ci.yml`](https://github.com/InitPHP/Escaper/blob/main/.github/workflows/ci.yml).

## Next step

Read [Quick Start](Quick-Start.md) for a 5-minute tour, or jump straight to the context page that matches your task — [HTML body](Context-HTML.md), [HTML attribute](Context-HTML-Attribute.md), [JavaScript](Context-JavaScript.md), [CSS](Context-CSS.md), [URL](Context-URL.md).
