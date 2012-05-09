<?php

//require_once __DIR__.'/lib/PHPUnit/Autoload.php';

require_once dirname(__DIR__).'/FCom/Core/Core.php';

FCom_Core::i()->init('FCom_Tests');
BModuleRegistry::i()->bootstrap();

//FCom_Test_AllTests::suite();