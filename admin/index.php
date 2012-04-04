<?php

$rootDir = dirname(__DIR__);
require $rootDir.'/lib/buckyball/buckyball.php';
require $rootDir.'/FCom/FCom.php';

$webRoot = BRequest::i()->webRoot(1);
BConfig::i()->add(array(
    'root_dir' => $rootDir,
    'web' => array(
        'base_store' => $webRoot,
        'base_src' => $webRoot,
    ),
));

FCom::i()->run('FCom_Admin');
