<?php
spl_autoload_register('autoload');

$ctr = 'mpf\\controller\\' . ($_GET['ctr'] ?? 'Dictionary');
$act = 'action' . ($_GET['act'] ?? 'index');

// header('Access-Control-Allow-Origin:*');
// header('Access-Control-Allow-Credentials:true');
// header('Access-Control-Allow-Methods:GET,POST,PUT');

(new $ctr())->$act();

function autoload($className)
{
    if (strpos($className, '\\') === false) {
        $file = "../src/controller/{$className}.php";
        if (file_exists($file)) {
            include_once ($file);
        }
    } else {
        $file = '../' . str_replace(['\\', 'mpf'], ['/', 'src'], $className) . '.php';
        if (file_exists($file)) {
            include_once ($file);
        }
    }
}
