<?php

require_once __DIR__ . '/core/FCom/Core/Main.php';

BConfig::i()->set('cookie/session_disable', true);

if (file_exists(__DIR__.'/cron.local.php')) {
    require_once __DIR__.'/cron.local.php';
}

if (PHP_SAPI === 'cli') {
    FCom_Core_Main::i()->init('FCom_Cron');
    FCom_Cron_Main::i()->run();
} else {
    FCom_Core_Main::i()->run('FCom_Cron');
}


