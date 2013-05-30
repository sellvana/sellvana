<?php return array(
    'modules' => array(
        // test CreditCard module
        'FCom_Ogone' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'translations' => array('de' => 'de.php'),
            'description' => "Ogone Payment Method",
            'bootstrap' => array('file'=>'Ogone.php', 'callback'=>'FCom_Ogone::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'OgoneAdmin.php', 'callback'=>'FCom_Ogone_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'OgoneFrontend.php', 'callback'=>'FCom_Ogone_Frontend::bootstrap'),
                ),
            ),

            "require" => array(
                "module" => array("FCom_Sales" => "0.1.10", 'FCom_Checkout' => '0.2.1'),
            ),

        ),
    ),
);