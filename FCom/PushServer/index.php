<?php

$rootDir = dirname(dirname(__DIR__));
require_once $rootDir . '/FCom/Core/Main.php';

$webRoot = $this->BRequest->webRoot(2);
$this->BConfig
    ->set('fs/root_dir', $rootDir)
    ->set('web/base_href', $webRoot)
;

$this->BDebug->mode('DEVELOPMENT');
$this->FCom_Core_Main->run('FCom_PushServer');
