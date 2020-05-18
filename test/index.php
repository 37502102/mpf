<?php
spl_autoload_register('autoload');

$ctr = 'mpf\\controller\\' . ($_GET['ctr'] ?? 'Dictionary');
$act = 'action' . ($_GET['act'] ?? 'index');

(new $ctr())->$act();

function autoload($className)
{
    if (strpos($className, '\\') === false) {
        include_once ("../src/controller/{$className}.php");
    } else {
        include_once ('../' . str_replace(['\\', 'mpf'], ['/', 'src'], $className) . '.php');
    }
}
