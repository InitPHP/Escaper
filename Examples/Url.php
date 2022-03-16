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
    <title>Unescaped URL data</title>
    <meta charset="UTF-8"/>
</head>
<body>
<?php
// http://example.com/?query=%22%20onmouseover%3D%22alert%28%27hello%27%29
?>
<a href="http://example.com/?query=<?php echo Esc::esc($query, 'url'); ?>">Click here!</a>
</body>
</html>