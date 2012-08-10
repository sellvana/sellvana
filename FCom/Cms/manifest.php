<?php return array(
    'modules' => array(
        // catalog views and controllers
        'FCom_Cms' => array(
            'version' => '0.1.1',
            'root_dir' => '',
            'depends' => array('FCom_Core', 'BPHPTAL'),
            'translations' => array('de' => 'de.csv'),
            'description' => "CMS for custom pages and forms",
            'bootstrap' => array('file'=>'CmsFrontend.php', 'callback'=>'FCom_Cms_Frontend::bootstrap'),
            'migrate' => 'FCom_Cms_Migrate',
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'CmsAdmin.php', 'callback'=>'FCom_Cms_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'CmsFrontend.php', 'callback'=>'FCom_Cms_Frontend::bootstrap'),
                ),
            ),
        ),





    ),
);