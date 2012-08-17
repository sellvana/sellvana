<?php return array(
    'modules' => array(
        // translations Admin module
        'FCom_Translation' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'depends' => array('FCom_Core'),
            'description' => "Translations module",
            'bootstrap' => array('file'=>'Translation.php', 'callback'=>'FCom_Translation::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'TranslationAdmin.php', 'callback'=>'FCom_Translation_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'TranslationFrontend.php', 'callback'=>'FCom_Translation_Frontend::bootstrap'),
                ),
            ),
        ),
    ),
);