<?php
require_once dirname(__DIR__).'/FCom/Core/Main.php';
BConfig::i()->set('fs/root_dir', dirname(__DIR__));
FCom_Core_Main::i()->init('FCom_Test');
BModuleRegistry::i()->bootstrap();
//BMigrate::migrateModules();