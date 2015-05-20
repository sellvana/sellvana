<?php

$rootDir = str_replace('\\', '/', dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))));
require_once $rootDir . '/core/FCom/Core/Main.php';
$webRoot = BRequest::i()->webRoot(3);
BConfig::i()
    ->set('fs/root_dir', $rootDir)
    ->set('web/base_href', $webRoot)
;

BDebug::i()->mode('DEVELOPMENT');
FCom_Core_Main::i()->run('FCom_PushServer');
