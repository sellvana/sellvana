<?php return array(
    'modules' => array(
        'FCom_Promo' => array(
            'version' => '0.1.2',
            'root_dir' => '',
            'require' => array('module'=>array('FCom_Core'=>'0.1.0')),
            'description' => "Promotions module",
            'migrate' => 'FCom_Promo_Migrate',
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'PromoAdmin.php', 'callback'=>'FCom_Promo_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'PromoFrontend.php', 'callback'=>'FCom_Promo_Frontend::bootstrap'),
                ),
            )
        ),
    ),
);