<?php

require_once __DIR__ . "/../vendor/autoload.php";

spl_autoload_register(function ($namespace) {
    if ($namespace === 'Phalcon\\Di') {
        include_once __DIR__. "/stub.phalcon.di.php";
        return;
    }

    if (strpos($namespace, 'Phalcon') === 0) {
        $phalconParts = explode("\\", $namespace);
        $phalcon = array_shift($phalconParts);
        $class = array_pop($phalconParts);

        // Nouvelle fonction pour gérer la casse des répertoires
        $phalconParts = array_map(function($part) {
            return ucfirst(strtolower($part));
        }, $phalconParts);

        $filePath = "$phalcon";
        $filePath .= ((empty($phalconParts)) ? '' : '/' . join('/', $phalconParts));
        $filePath .= "/$class.php";

        include_once __DIR__. "/../vendor/phalcon/ide-stubs/src/$filePath";
    }
});
