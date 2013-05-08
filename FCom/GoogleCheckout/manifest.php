<?php return array(
    'modules' => array(
        'FCom_GoogleCheckout' => array(
            'version'     => '0.1.0',
            'root_dir'    => '',
            //'migrate' => 'FCom_GoogleCheckout_Migrate',
            'require'     => array(
                'module' => array('FCom_Checkout' => '0.1.0'),
            ),
            'tests'       => "FCom_GoogleCheckout_Tests_AllTests",
            'bootstrap'   => array('file' => 'GoogleCheckout.php', 'callback' => 'FCom_GoogleCheckout::bootstrap'),
            'areas'       => array(
                'FCom_Admin'    => array(
                    'bootstrap' => array(
                        'file' => 'GoogleCheckoutAdmin.php', 'callback' => 'FCom_GoogleCheckout_Admin::bootstrap'
                    ),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array(
                        'file' => 'GoogleCheckoutFrontend.php', 'callback' => 'FCom_GoogleCheckout_Frontend::bootstrap'
                    ),
                ),
            ),
            'description' => "Google Checkout payment method",
            'title' => 'Google Checkout',
            'sandbox'     => array(
                'url' => "sandbox.google.com/checkout/api/checkout/v2/request/Merchant"
            ),
            'production'  => array(
                'url' => "checkout.google.com/api/checkout/v2/request/Merchant"
            ),
        ),
    ),
);