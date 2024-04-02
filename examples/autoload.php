<?php
$rootDir = dirname(__DIR__, 1);
require $rootDir . '/vendor/autoload.php';

spl_autoload_register(function($class) {
    $baseDir = dirname(__DIR__, 1) . '/src';
    $offset = strlen('Haoa\\MixDb\\');
    $path = substr($class, $offset, strlen($class));
    $path = $baseDir . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $path) . '.php';
    require($path);
});