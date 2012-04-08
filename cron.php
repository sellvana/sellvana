<?php

require_once __DIR__.'/lib/buckyball/buckyball.php';
require_once __DIR__.'/FCom/FCom.php';

if (file_exists(__DIR__.'cron.local.php')) {
    require_once __DIR__.'cron.local.php';
}
FCom::i()->run('FCom_Cron');
