<?php

require_once __DIR__.'/FCom/Core/Core.php';

BConfig::i()->set('cookie/session_disable', true);

if (file_exists(__DIR__.'cron.local.php')) {
    require_once __DIR__.'cron.local.php';
}
FCom_Core::i()->run('FCom_Cron');
