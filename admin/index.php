<?php

require dirname(__DIR__).'/FCom/FCom.php';

BConfig::i()->add(array('modules'=>array(
    'Denteva_Admin'=>array('run_level'=>BModule::REQUIRED),
    'Denteva_Merge'=>array('run_level'=>BModule::REQUIRED),
)));

FCom::i()->run('FCom_Admin');