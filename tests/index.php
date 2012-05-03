<?php

//require_once __DIR__.'/lib/PHPUnit/Autoload.php';

require_once realpath(dirname(__FILE__) . '/..').'/lib/buckyball/buckyball.php';
require_once realpath(dirname(__FILE__) . '/..').'/FCom/FCom.php';

FCom::i()->init('FCom_Tests');
BModuleRegistry::i()->bootstrap();

//FCom_Test_AllTests::suite();