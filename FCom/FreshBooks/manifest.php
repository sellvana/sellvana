<?php return array(
    'modules' => array(
        // freshbook simple invoicing
        'FCom_FreshBooks' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'require' => array('module'=>array('FCom_Core'=>'0.1.0')),
            'description' => "FreshBooks&reg; payment method and invoice management API integration",
            'bootstrap' => array('file'=>'FreshBooksFrontend.php', 'callback'=>'FCom_FreshBooks_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'FreshBooksAdmin.php', 'callback'=>'FCom_FreshBooks_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'FreshBooksFrontend.php', 'callback'=>'FCom_FreshBooks_Frontend::bootstrap'),
                ),
            ),
        ),




    ),
);