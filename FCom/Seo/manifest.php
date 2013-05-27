<?php return array(
    'modules' => array(
        'FCom_Seo' => array(
            'version' => '0.1.0',
            'description' => "SEO related goodies, such as sitemaps and URL aliases",
            'bootstrap' => array('file'=>'Seo.php', 'callback'=>'FCom_Seo::bootstrap'),
            'migrate' => 'FCom_Seo_Migrate',
            'areas' => array(
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'SeoFrontend.php', 'callback'=>'FCom_Seo_Frontend::bootstrap'),
                ),
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'SeoAdmin.php', 'callback'=>'FCom_Seo_Admin::bootstrap'),
                ),
            ),
            "require" => array(
                "module" => array("FCom_Core" => "0.1.0"),
            ),
        ),
    ),
);