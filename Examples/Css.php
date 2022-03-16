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