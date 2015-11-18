<?php
// This is global bootstrap for autoloading
require_once dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/FCom/Core/Main.php';
if (defined('FULLERON_ROOT_DIR')) {
    BConfig::i()->set('fs/root_dir', FULLERON_ROOT_DIR);
} else {
    BConfig::i()->set('fs/root_dir', dirname(dirname(dirname(__DIR__))));
}
FCom_Core_Main::i()->init('FCom_Test');
BModuleRegistry::i()->bootstrap();
//BMigrate::migrateModules();

//$bmode = BDebug::i()->mode();
BDebug::i()->mode(BDebug::MODE_DISABLED);
