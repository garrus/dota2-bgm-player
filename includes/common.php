<?php
define('DS', DIRECTORY_SEPARATOR);
ini_set('xdebug.var_display_max_depth', 8);
spl_autoload_register(function($className) {
    $classFile = __DIR__. DS. 'class'. DS. $className. '.php';
    if (is_file($classFile)) {
        require $classFile;
        if (!class_exists($className, false)) {
            die('Cannot load class '. $className. ' at file '. $classFile);
        }
    }
});