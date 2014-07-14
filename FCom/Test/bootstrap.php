<?php
require_once dirname(__DIR__) . '/../FCom/Core/Main.php';
if (defined('FULLERON_ROOT_DIR')) {
    BConfig::i()->set('fs/root_dir', FULLERON_ROOT_DIR);
} else {
    BConfig::i()->set('fs/root_dir', dirname(dirname(__DIR__ . '/../')));
}
FCom_Core_Main::i()->init('FCom_Test');
BModuleRegistry::i()->bootstrap();
//BMigrate::migrateModules();
