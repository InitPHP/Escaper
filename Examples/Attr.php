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