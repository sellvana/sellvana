<?php

$rootDir = dirname(__DIR__);
require_once $rootDir.'/lib/buckyball/buckyball.php';
require_once $rootDir.'/FCom/Core/Core.php';

$webRoot = BRequest::i()->webRoot(1);
BConfig::i()->add(array(
    'fs' => array(
        'root_dir' => $rootDir,
    ),
    'web' => array(
        'base_store' => $webRoot,
        'base_src' => $webRoot,
    ),
));

if (file_exists(__DIR__.'index.local.php')) {
    require_once __DIR__.'index.local.php';
}

BDebug::mode('DEBUG');
FCom_Core::i()->run('FCom_Api');
