<?php return array(
    'modules' => array(

        // FCom_Market description here
        'FCom_Market' => array(
            'version' => '0.1.4',
            'root_dir' => '',
            'bootstrap' => array('file'=>'MarketApi.php', 'callback'=>'FCom_Market_MarketApi::bootstrap'),
            'require' => array('module'=>array('FCom_Core'=>'0.1.0')),
            'migrate' => 'FCom_Market_Migrate',
            'description' => "FCom_Market description here",
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'MarketAdmin.php', 'callback'=>'FCom_Market_Admin::bootstrap'),
                ),
                "FCom_Cron" => array(
                    "bootstrap" => array("file" => "Cron.php", "callback" => "FCom_Market_Cron::bootstrap")
                )
            ),
        ),





    ),
);