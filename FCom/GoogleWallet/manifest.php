<?php return [
    'modules' => [

        // cart, checkout and customer account views and controllers
        'FCom_GoogleWallet' => [
            'version' => '0.1.0',
            //'migrate' => 'FCom_GoogleWallet_Migrate',
            'require' => [
                'module' => ['FCom_Checkout' => '0.1.0'],
            ],
            //'translations' => array('en' => 'en.php'),
            'description' => "Google Wallet payment method",
        ],





    ],
];