<?php return array(
    'modules' => array(
        // PHPUnit Tests integration
        'FCom_Test' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'require' => array('module'=>array('FCom_Core'=>'0.1.0')),
            'description' => "PHPUnit tests integration",
            'bootstrap' => array('file'=>'Test.php', 'callback'=>'FCom_Test::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'TestAdmin.php', 'callback'=>'FCom_Test_Admin::bootstrap')
                ),
            ),
        ),
    ),
);