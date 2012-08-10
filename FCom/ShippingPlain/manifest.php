<?php return array(
    'modules' => array(
        'FCom_ShippingPlain' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'depends' => array('FCom_Checkout'),
            'bootstrap' => array('file'=>'ShippingPlain.php', 'callback'=>'FCom_ShippingPlain::bootstrap'),
            'areas' => array(
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'ShippingPlain.php', 'callback'=>'FCom_ShippingPlain::bootstrap'),
                ),
            ),
            'description' => "Plain shipping module for checkout",
        ),
    ),
);