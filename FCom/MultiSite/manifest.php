<?php return array(
    'modules' => array(
        'FCom_MultiSite' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'translations' => array('de' => 'de.php'),
            'description' => "Enable multiple website management from the same instance",
            'migrate' => 'FCom_MultiSite_Migrate',

            'before_bootstrap' => array('file'=>'MultiSite.php', 'callback'=>'FCom_MultiSite::beforeBootstrap'),
            'bootstrap' => array('file'=>'MultiSite.php', 'callback'=>'FCom_MultiSite::bootstrap'),
            "require" => array("module" => array("FCom_Core" => "0.1.0")),

            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'MultiSiteAdmin.php', 'callback'=>'FCom_MultiSite_Admin::bootstrap'),
                    'require' => array('module' => array('FCom_Admin' => '0.1.0')),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'MultiSiteFrontend.php', 'callback'=>'FCom_MultiSite_Frontend::bootstrap'),
                    'require' => array('module' => array('FCom_Frontend' => '0.1.0')),
                ),
            ),
        ),
    ),
);