<?php

if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    header('HTTP/1.1 503 Service Unavailable');
    header('Status: 503 Service Unavailable');
    die('<h1>Unsupported PHP version: ' . PHP_VERSION . '</h1><p>PHP 5.4.0 or higher required</p>');
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/core/FCom/Core/Main.php';

$storeRoot = BRequest::i()->webRoot(1);
BConfig::i()
    ->set('fs/root_dir', $rootDir)
    ->set('web/base_src', $storeRoot)
    ->set('web/base_store', $storeRoot)
    ->set('web/base_href', BRequest::i()->webRoot())
;
if (file_exists(__DIR__.'/index.local.php')) {
    require_once __DIR__.'/index.local.php';
}
#error_reporting(E_ALL | E_STRICT);

FCom_Core_Main::i()->run('FCom_Admin');
