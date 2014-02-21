<?php

$rootDir = dirname(__DIR__);
require_once $rootDir.'/FCom/Core/Main.php';

//$webRoot = BRequest::i()->webRoot();
BConfig::i()
    ->set('fs/root_dir', $rootDir)
    ->set('web/base_src', BRequest::i()->webRoot(1))
    ->set('web/base_href', BRequest::i()->webRoot())
;
if (file_exists(__DIR__.'/index.local.php')) {
    require_once __DIR__.'/index.local.php';
}
error_reporting(E_ALL | E_STRICT);

BDebug::mode('DEBUG');
FCom_Core_Main::i()->run('FCom_Admin');
