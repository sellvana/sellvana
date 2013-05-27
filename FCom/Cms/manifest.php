<?php return array(
    'modules' => array(
        // catalog views and controllers
        'FCom_Cms' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'require' => array('module'=>array('FCom_Core'=>'0.1.0', 'BTwig'=>'1.12.4')),
            'translations' => array('de' => 'de.php'),
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