<?php
spl_autoload_register('autoload');

$ctr = 'mpf\\controller\\' . str_replace('mpf_', '', $_GET['ctr'] ?? 'mpf_Dictionary');
$act = 'action' . ($_GET['act'] ?? 'index');

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
