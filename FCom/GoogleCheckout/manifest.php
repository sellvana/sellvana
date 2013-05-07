<?php return array(
    'modules' => array(

        // cart, checkout and customer account views and controllers
        'FCom_GoogleCheckout' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            //'migrate' => 'FCom_GoogleCheckout_Migrate',
            'require' => array(
                'module' => array('FCom_Checkout'=>'0.1.0'),
            ),
            //'translations' => array('de' => 'de.php'),
            'tests' => "FCom_GoogleCheckout_Tests_AllTests",
            'bootstrap' => array('file'=>'GoogleCheckout.php', 'callback'=>'FCom_GoogleCheckout::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'GoogleCheckoutAdmin.php', 'callback'=>'FCom_GoogleCheckout_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'GoogleCheckoutFrontend.php', 'callback'=>'FCom_GoogleCheckout_Frontend::bootstrap'),
                ),
            ),
            'description' => "Google Checkout payment method",
        ),





    ),
);