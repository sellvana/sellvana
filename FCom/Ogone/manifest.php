<?php return array(
    'modules' => array(
        // test CreditCard module
        'FCom_Ogone' => array(
            'version' => '0.1.0',
            'translations' => array('de' => 'de.php'),
            'description' => "Ogone Payment Method",
            "require" => array(
                "module" => array("FCom_Sales" => "0.1.10", 'FCom_Checkout' => '0.2.1'),
            ),

        ),
    ),
);