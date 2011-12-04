<?php

require __DIR__.'/FCom/FCom.php';

BConfig::i()->add(array('modules'=>array(
    'Denteva_Catalog' => array('run_level'=>BModule::REQUIRED),
    'Denteva_Discover' => array('run_level'=>BModule::REQUIRED),
)));

FCom::i()->run('FCom_Frontend');