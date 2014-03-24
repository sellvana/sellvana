<?php return array(
    'modules' => array(

        // cart, checkout and customer account views and controllers
        'FCom_GoogleWallet' => array(
            'version' => '0.1.0',
            //'migrate' => 'FCom_GoogleWallet_Migrate',
            'require' => array(
                'module' => array('FCom_Checkout'=>'0.1.0'),
            ),
            //'translations' => array('en' => 'en.php'),
            'description' => "Google Wallet payment method",
        ),





    ),
);