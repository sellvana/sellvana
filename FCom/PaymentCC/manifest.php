<?php return array(
    'modules' => array(
        // test CreditCard module
        'FCom_PaymentCC' => array(
            'version' => '0.1.0',
            'translations' => array('de' => 'de.php'),
            'description' => "Payment Credit Card test module",
            "require" => array("module" => array("FCom_Sales" => "0.1.10")),
        ),
    ),
);