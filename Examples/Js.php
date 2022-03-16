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