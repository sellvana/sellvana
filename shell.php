#!/usr/bin/php
<?php

$t = microtime(true);

require_once __DIR__ . '/core/FCom/Core/Main.php';

if (PHP_SAPI !== 'cli') {
    #echo "Available only for CLI.";
    #die;
}

#BConfig::i()->set('fs/root_dir', __DIR__);

FCom_Core_Main::i()->init('FCom_Shell');

FCom_Shell_Shell::i()->run();

echo FCom_Shell_Shell::i()->colorize(
    "\n{.black*}Total run time: {black_}" . sprintf('%2.5f', microtime(true) - $t) . "{.black*}, " .
    "Peak memory: {black_}" . memory_get_peak_usage() . "{.black*}.{/}\n"
);
