<?php return array(
    'modules' => array(

        // cart, checkout and customer account views and controllers
        'FCom_Checkout' => array(
            'version' => '0.2.1',
            'root_dir' => '',
            'migrate' => 'FCom_Checkout_Migrate',
            'require' => array('module'=>array('FCom_Catalog'=>'0.2.1', 'FCom_Geo'=>'0.1.0', 'FCom_Sales'=>'0.1.10')),
            'translations' => array('de' => 'de.php'),
            'tests' => "FCom_Checkout_Tests_AllTests",
            'bootstrap' => array('file'=>'Checkout.php', 'callback'=>'FCom_Checkout::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'CheckoutAdmin.php', 'callback'=>'FCom_Checkout_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'CheckoutFrontend.php', 'callback'=>'FCom_Checkout_Frontend::bootstrap'),
                ),
            ),
            'description' => "Base cart and checkout functionality",
        ),
    ),
);