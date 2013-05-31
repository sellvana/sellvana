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
            "require" => array("FCom_Checkout"),
        ),
    ),
);