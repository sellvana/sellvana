<?php return array(
    'modules' => array(
        // test CreditCard module
        'FCom_CreditCard' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'depends' => array('FCom_Core'),
            'translations' => array('de' => 'de.csv'),
            'description' => "Credit Card test module",
            'bootstrap' => array('file'=>'CreditCardFrontend.php', 'callback'=>'FCom_CreditCard_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'CreditCardFrontend.php', 'callback'=>'FCom_CreditCard_Frontend::bootstrap'),
                ),
            ),
        ),




    ),
);