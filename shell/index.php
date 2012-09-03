<?php

$rootDir = dirname(__DIR__);
require_once $rootDir.'/FCom/Core/Core.php';

BConfig::i()->set('fs/root_dir', dirname(__DIR__));
FCom_Core::i()->init('FCom_Shell');
