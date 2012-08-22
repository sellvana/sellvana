<?php return array(
    'modules' => array(
        'FCom_ShippingPlain' => array(
            'author'    => 'Fulleron Inc',
            'title' => 'Shipping Plain module',
            'description' => "Plain shipping module for checkout",
            'category' => array('Shipping'),
            'depends' => array('FCom_Checkout'),
            'version' => '0.1.0',
            'license'   => array('GPL3'),

            'root_dir' => '',
            'bootstrap' => array('file'=>'ShippingPlain.php', 'callback'=>'FCom_ShippingPlain::bootstrap'),
            'areas' => array(
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'ShippingPlain.php', 'callback'=>'FCom_ShippingPlain::bootstrap'),
                ),
            ),
        ),
    ),
);