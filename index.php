<?php

require_once __DIR__.'/lib/buckyball/buckyball.php';
require_once __DIR__.'/FCom/FCom.php';

if (file_exists(__DIR__.'index.local.php')) {
    require_once __DIR__.'index.local.php';
}
BDebug::mode('RECOVERY');
FCom::i()->run('FCom_Frontend');
