<?php

if (PHP_SAPI !== 'cli') {
    echo "Available only for CLI.";
    die;
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/core/FCom/Core/Main.php';

BConfig::i()->set('fs/root_dir', dirname(__DIR__));
FCom_Core_Main::i()->init('FCom_Shell');

FCom_Shell_Shell::i()->run();