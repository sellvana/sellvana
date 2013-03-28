<?php return array(
    'modules' => array(
        // test CreditCard module
        'FCom_PaymentBasic' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'translations' => array('de' => 'de.php'),
            'description' => "Basic payment methods (check/money order, purchase order)",
            'bootstrap' => array('file'=>'PaymentBasicFrontend.php', 'callback'=>'FCom_PaymentBasic_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'PaymentBasicFrontend.php', 'callback'=>'FCom_PaymentCBasic_Frontend::bootstrap'),
                ),
            ),

            "require" => array(
                "module" => array("FCom_Core" => "0.1.0"),
                "class" => array("PHPUnit"),
                "phpext" => array("curl"),
            ),

        ),
    ),
);