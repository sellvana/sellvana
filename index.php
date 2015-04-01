<?php

if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    header('HTTP/1.1 503 Service Unavailable');
    header('Status: 503 Service Unavailable');
    die('<h1>Unsupported PHP version: ' . PHP_VERSION . '</h1><p>PHP 5.4.0 or higher required</p>');
}

require_once __DIR__ . '/core/FCom/Core/Main.php';

if (file_exists(__DIR__.'/index.local.php')) {
    require_once __DIR__.'/index.local.php';
}

FCom_Core_Main::i()->run('FCom_Frontend');
