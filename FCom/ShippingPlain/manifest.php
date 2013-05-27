<?php return array(
    'modules' => array(
        'FCom_ShippingPlain' => array(
            'author'    => 'Fulleron Inc',
            'title' => 'Shipping Plain module',
            'description' => "Plain shipping module for checkout",
            'category' => array('Shipping'),
            'require' => array('module'=>array('FCom_Checkout'=>'0.2.1')),
            'version' => '0.1.0',
            'license'   => array('GPL3'),

            'root_dir' => '',
            'bootstrap' => array('file'=>'ShippingPlain.php', 'callback'=>'FCom_ShippingPlain::bootstrap'),
            'areas' => array(
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'ShippingPlain.php', 'callback'=>'FCom_ShippingPlain::bootstrap'),
                ),
            ),
            "require" => array("FCom_Checkout"),
        ),
    ),
);