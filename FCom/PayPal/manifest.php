<?php return array(
    'modules' => array(

        // paypal IPN
        'FCom_PayPal' => array(
            'version' => '0.1.0',
            'require' => array('module'=>array('FCom_Sales'=>'0.1.10')),
            'translations' => array('de' => 'de.php'),
            'description' => "PayPal&reg; standard payment method",
        ),
    ),
);