<?php return array(
    'modules' => array(
        // IndexTank integration
        'FCom_IndexTank' => array(
            'version' => '0.2.1',
            'root_dir' => '',
            'translations' => array('de' => 'de.php'),
            'description' => "IndexTank API integration",
            'migrate' => 'FCom_IndexTank_Migrate',
            'tests' => "FCom_IndexTank_Tests_AllTests",
            'bootstrap' => array('file'=>'IndexTankFrontend.php', 'callback'=>'FCom_IndexTank_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'IndexTankAdmin.php', 'callback'=>'FCom_IndexTank_Admin::bootstrap'),
                    'require' => array('module'=>array('BGanon'=>'0.5.0')),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'IndexTankFrontend.php', 'callback'=>'FCom_IndexTank_Frontend::bootstrap'),
                    //'require' => array(array('FCom_Frontend'=>'0.1.0')),
                ),
                "FCom_Cron" => array(
                    "bootstrap" => array("file" => "Cron.php", "callback" => "FCom_IndexTank_Cron::bootstrap")
                )
            ),
            "require" => array(
                "module" => array("FCom_Core" => "0.1.0"),
                "class" => array("PHPUnit"),
                "phpext" => "curl",
            ),
        ),



    ),
);