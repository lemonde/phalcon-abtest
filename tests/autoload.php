<?php

require_once __DIR__ . "/../vendor/autoload.php";

spl_autoload_register(function ($namespace): void {
    if ($namespace === \Phalcon\Di\Di::class) {
        // for implement getDefault() method
        include_once __DIR__. "/stub.phalcon.di.php";
        return;
    }
    if ($namespace === \Phalcon\Support\Collection::class) {
        // the \Serializable interface is not implemented correctly it does not respect the method signature
        include_once __DIR__. "/stub.phalcon.collection.php";
        return;
    }

    if (str_starts_with((string) $namespace, 'Phalcon')) {
        $phalconParts = explode("\\", (string) $namespace);
        $phalcon = array_shift($phalconParts);
        $class = array_pop($phalconParts);

        // Nouvelle fonction pour gérer la casse des répertoires
        $phalconParts = array_map(fn($part) => ucfirst(strtolower((string) $part)), $phalconParts);

        $filePath = ((empty($phalconParts)) ? '' : '/' . join('/', $phalconParts));
        $filePath .= "/$class.php";

        include_once __DIR__. "/../vendor/phalcon/ide-stubs/src/$filePath";
    }
});
