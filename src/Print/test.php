<?php

use Tabula17\Satelles\Utilis\Print\CupsClient;

require __DIR__ . '/../../vendor/autoload.php';


Swoole\Coroutine\run(function () {
    $cups = new CupsClient('localhost');

    try {
        // Método preferido (IPP)
        $version = $cups->getVersion();
        echo "Versión CUPS (IPP): $version\n";

        // Método alternativo (HTTP)
        $versionHttp = $cups->getVersionHttp();
        echo "Versión CUPS (HTTP): $versionHttp\n";

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo var_export($cups->getPrinters(), true);
});