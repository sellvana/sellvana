<?php return array(
    'modules' => array(
        // IndexTank integration
        'FCom_ApiTest' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'require' => array('module'=>array('FCom_Core'=>'0.1.0')),
            'description' => "API Test",
            'bootstrap' => array('file'=>'ApiTest.php', 'callback'=>'FCom_ApiTest::bootstrap')
        ),
    ),
);