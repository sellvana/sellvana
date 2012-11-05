<?php return array(
    'modules' => array(
        'FCom_Promo' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'depends' => array('FCom_Core'),
            'description' => "Promotions module",
            'migrate' => 'FCom_Promo_Migrate',
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'PromoAdmin.php', 'callback'=>'FCom_Promo_Admin::bootstrap'),
                ),
            )
        ),
    ),
);