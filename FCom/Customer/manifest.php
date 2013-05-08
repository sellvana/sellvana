<?php return array(
    'modules' => array(
        // customer account and management
        'FCom_Customer' => array(
            'version' => '0.1.3',
            'root_dir' => '',
            'depends' => array('FCom_Core', 'FCom_Geo'),
            'translations' => array('de' => 'de.php'),
            'tests' => "FCom_Customer_Tests_AllTests",
            'description' => "Customer Accounts and Management",
            'migrate' => 'FCom_Customer_Migrate',
            'bootstrap' => array('file'=>'CustomerFrontend.php', 'callback'=>'FCom_Customer_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Api' => array(
                    'bootstrap' => array('file'=>'Api.php', 'callback'=>'FCom_Customer_Api::bootstrap'),
                ),
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'CustomerAdmin.php', 'callback'=>'FCom_Customer_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'CustomerFrontend.php', 'callback'=>'FCom_Customer_Frontend::bootstrap'),
                ),
            ),
            "require" => array(
                "module" => "FCom_Core",
                "class" => "PHPUnit",
                ),


        ),

    ),
);