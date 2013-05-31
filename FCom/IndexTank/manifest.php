<?php return array(
    'modules' => array(
        // IndexTank integration
        'FCom_IndexTank' => array(
            'version' => '0.2.1',
            'translations' => array('de' => 'de.php'),
            'description' => "IndexTank API integration",
            'areas' => array(
                'FCom_Admin' => array(
                    'require' => array('module'=>array('BGanon'=>'0.5.0')),
                ),
            ),
            "require" => array(
                "module" => array("FCom_Core" => "0.1.0"),
            ),
        ),



    ),
);