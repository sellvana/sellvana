<?php

require_once __DIR__ . '/core/FCom/Core/Main.php';

BConfig::i()->set('cookie/session_disable', true);

if (file_exists(__DIR__.'/cron.local.php')) {
    require_once __DIR__.'/cron.local.php';
}

$handles = [];
$force = false;

if (PHP_SAPI === 'cli') {
    for ($i = 0; $i < $argc; $i++) {
        $a = $argv[$i];
        if ($a === '-f') {
            $force = true;
        } elseif ($a === '-h') {
            $handles[] = $argv[++$i];
        }
    }
}

FCom_Core_Main::i()->init('FCom_Cron');
FCom_Cron_Main::i()->run($handles, $force);
