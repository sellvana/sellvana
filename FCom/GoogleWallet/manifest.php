<?php return array(
    'modules' => array(

        // cart, checkout and customer account views and controllers
        'FCom_GoogleWallet' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            //'migrate' => 'FCom_GoogleWallet_Migrate',
            'require' => array(
                'module' => array('FCom_Checkout'=>'0.1.0'),
            ),
            //'translations' => array('de' => 'de.php'),
            'tests' => "FCom_GoogleWallet_Tests_AllTests",
            'bootstrap' => array('file'=>'GoogleWallet.php', 'callback'=>'FCom_GoogleWallet::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'GoogleWalletAdmin.php', 'callback'=>'FCom_GoogleWallet_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'GoogleWalletFrontend.php', 'callback'=>'FCom_GoogleWallet_Frontend::bootstrap'),
                ),
            ),
            'description' => "Google Wallet payment method",
        ),





    ),
);