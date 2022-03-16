<?php
require_once "../vendor/autoload.php";
use \InitPHP\Escaper\Esc;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Encodings set correctly!</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
<?php

$input = '<script>alert("initphp")</script>';

// &lt;script&gt;alert(&quot;initphp&quot;)&lt;/script&gt;
echo Esc::esc($input, 'html');

?>
</body></html>