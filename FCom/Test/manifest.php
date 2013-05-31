<?php return array(
    'modules' => array(
        // PHPUnit Tests integration
        'FCom_Test' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'require' => array(
            	'module'=>array('FCom_Core'=>'0.1.0'),
            	'class' => array('PHPUnit'),
            ),
            'description' => "PHPUnit tests integration",
        ),
    ),
);