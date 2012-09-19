<?php return array(
    'modules' => array(
        // test CreditCard module
        'FCom_PaymentCC' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'depends' => array('FCom_Core'),
            'translations' => array('de' => 'de.php'),
            'description' => "Payment Credit Card test module",
            'bootstrap' => array('file'=>'PaymentCCFrontend.php', 'callback'=>'FCom_PaymentCC_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'PaymentCCFrontend.php', 'callback'=>'FCom_PaymentCC_Frontend::bootstrap'),
                ),
            ),

            "require" => array(
                "module" => array("FCom_Core" => "0.1.0", "FCom_PayPal"),
                "class" => array("PHPUnit", "BDb"),
                "phpext" => array("curl"),
            ),

        ),
    ),
);