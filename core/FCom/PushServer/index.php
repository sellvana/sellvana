<?php

$rootDir = str_replace('\\', '/', dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))));
require_once $rootDir . '/core/FCom/Core/Main.php';
$webRoot = BRequest::i()->webRoot(3);
BConfig::i()
    ->set('fs/root_dir', $rootDir)
    ->set('web/base_href', $webRoot)
;

if (file_exists($rootDir . '/index.global.php')) {
    require_once $rootDir . '/index.global.php';
}

if (file_exists(__DIR__ . '/index.local.php')) {
    require_once __DIR__ . '/index.local.php';
}

BDebug::i()->mode('DEVELOPMENT');
FCom_Core_Main::i()->run('FCom_PushServer');
