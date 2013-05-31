<?php return array(
    'modules' => array(
        // test CreditCard module
        'FCom_PaymentBasic' => array(
            'version' => '0.1.0',
            'translations' => array('de' => 'de.php'),
            'description' => "Basic payment methods (check/money order, purchase order)",
            "require" => array(
                "module" => array("FCom_Sales" => "0.1.10"),
            ),

        ),
    ),
);