<?php return array(
    'modules' => array(
        // IndexTank integration
        'FCom_IndexTank' => array(
            'version' => '0.2.1',
            'root_dir' => '',
            'depends' => array('FCom_Core'),
            'translations' => array('de' => 'de.php'),
            'description' => "IndexTank API integration",
            'migrate' => 'FCom_IndexTank_Migrate',
            'tests' => "FCom_IndexTank_Tests_AllTests",
            'bootstrap' => array('file'=>'IndexTankFrontend.php', 'callback'=>'FCom_IndexTank_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'IndexTankAdmin.php', 'callback'=>'FCom_IndexTank_Admin::bootstrap'),
                    'depends' => array('BGanon'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'IndexTankFrontend.php', 'callback'=>'FCom_IndexTank_Frontend::bootstrap'),
                    //'depends' => array('FCom_Frontend'),
                ),
                "FCom_Cron" => array(
                    "bootstrap" => array("file" => "Cron.php", "callback" => "FCom_IndexTank_Cron::bootstrap")
                )
            ),
            "require" => array(
                "module" => array("FCom_Core" => "1.10.0;2.0.0"),
                "class" => array("PHPUnit"),
                "phpext" => array("curl"),
            ),
        ),



    ),
);