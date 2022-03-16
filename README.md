# InitPHP Escaper

Securely and safely escape HTML, HTML attributes, JavaScript, CSS, and URLs.

[![Latest Stable Version](http://poser.pugx.org/initphp/escaper/v)](https://packagist.org/packages/initphp/escaper) [![Total Downloads](http://poser.pugx.org/initphp/escaper/downloads)](https://packagist.org/packages/initphp/escaper) [![Latest Unstable Version](http://poser.pugx.org/initphp/escaper/v/unstable)](https://packagist.org/packages/initphp/escaper) [![License](http://poser.pugx.org/initphp/escaper/license)](https://packagist.org/packages/initphp/escaper) [![PHP Version Require](http://poser.pugx.org/initphp/escaper/require/php)](https://packagist.org/packages/initphp/escaper)

## Requirements

- PHP 7.4 or higher
- PHP _CType_ Extension
- PHP _MB_String_ or _Iconv_ Extension

## Installation

```php 
composer require initphp/escaper
```

## Usage

`\InitPHP\Escaper\Esc::esc()` : 

```php 
public static function esc(string[]|string $data, string $context = 'html', ?string $encoding = null): array|string;
```

- `$data` : The content to be cleared.
- `$context` : The method to be used for cleaning. If the value is not one of the following; Throws `Exception`.
  - `html` 
  - `js` 
  - `css` 
  - `url` 
  - `attr`
- `$encoding` : If the character set to be used is not specified or `NULL`; `UTF-8` is used by default.

`html` Escaper Example :
```php 
<?php
require_once "vendor/autoload.php";
use \InitPHP\Escaper\Esc;

$input = '<script>alert("initphp")</script>';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Encodings set correctly!</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>

<?php
echo Esc::esc($input, 'html');
?>
</body></html>
```

`attr` Escaper Example :

```php
<?php
require_once "../vendor/autoload.php";
use \InitPHP\Escaper\Esc;

$input = 'faketitle onmouseover=alert(/InitPHP!/);';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quoteless Attribute</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
<div>
    <?php
    // <span title=faketitle&#x20;onmouseover&#x3D;alert&#x28;&#x2F;InitPHP&#x21;&#x2F;&#x29;&#x3B;>
    ?>
    <span title=<?php echo Esc::esc($input, 'attr'); ?>>
            Hello World
    </span>
</div>
</body>
</html>
```

`Js` Escaper Example :

```php
<?php
require_once "../vendor/autoload.php";
use InitPHP\Escaper\Esc;

$input = 'bar&quot;; alert(&quot;Hello!&quot;); var xss=&quot;true';
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Escaped Entities</title>
    <meta charset="UTF-8"/>
    <script type="text/javascript">
        <?php
        /**
         * var foo = bar\x26quot\x3B\x3B\x20alert\x28\x26quot\x3BHello\x21\x26quot\x3B\x29\x3B\x20var\x20xss\x3D\x26quot\x3Btrue;
         */
        ?>
        var foo = <?php echo Esc::esc($input, 'js'); ?>;
    </script>
</head>
<body>
<p>Hello World</p>
</body>
</html>
```

`css` Escaper Example :

```php
<?php
require_once "../vendor/autoload.php";
use \InitPHP\Escaper\Esc;

$input = <<<INPUT
body {
    background-image: url('http://example.com/bar.jpg?</style><script>alert(13)</script>');
}
INPUT;
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Escaped CSS</title>
    <meta charset="UTF-8"/>
    <style>
        <?php
        /**
        * body\20 \7B \D \A \20 \20 \20 \20 background\2D image\3A \20 url\28 \27 http\3A \2F \2F example\2E com\2F bar\2E jpg\3F \3C \2F style\3E \3C script\3E alert\28 13\29 \3C \2F script\3E \27 \29 \3B \D \A \7D
        */
        echo Esc::esc($input, 'css');
        ?>
    </style>
</head>
<body>
<p>User controlled CSS needs to be properly escaped!</p>
</body>
</html>
```

`url` Escaper Example : 

```php
<?php
require_once "../vendor/autoload.php";
use \InitPHP\Escaper\Esc;

$query = <<<QUERY
" onmouseover="alert('hello')
QUERY;
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Unescaped URL</title>
    <meta charset="UTF-8"/>
</head>
<body>
<?php
// http://example.com/?query=%22%20onmouseover%3D%22alert%28%27hello%27%29
?>
<a href="http://example.com/?query=<?php echo Esc::esc($query, 'url'); ?>">Click</a>
</body>
</html>
```

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Copyright &copy; 2022 [MIT License](./LICENSE)
