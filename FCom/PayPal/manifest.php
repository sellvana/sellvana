<?php return array(
    'modules' => array(

        // paypal IPN
        'FCom_PayPal' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'depends' => array('FCom_Core'),
            'translations' => array('de' => 'de.php'),
            'description' => "PayPal&reg; standard payment method",
            'bootstrap' => array('file'=>'PayPalFrontend.php', 'callback'=>'FCom_PayPal_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'PayPalAdmin.php', 'callback'=>'FCom_PayPal_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'PayPalFrontend.php', 'callback'=>'FCom_PayPal_Frontend::bootstrap'),
                ),
            ),
        ),




    ),
);