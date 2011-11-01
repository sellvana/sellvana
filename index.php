<?php

require __DIR__.'/FCom/FCom.php';

BConfig::i()->add(array('config_dir'=>'storage/config'));

FCom::i()->run('FCom_Frontend');