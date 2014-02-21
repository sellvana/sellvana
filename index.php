<?php
require_once __DIR__.'/FCom/Core/Main.php';

if (file_exists(__DIR__.'/index.local.php')) {
    require_once __DIR__.'/index.local.php';
}

BDebug::mode('DEBUG');
FCom_Core_Main::i()->run('FCom_Frontend');
