<?php return array(
    'modules' => array(

        'FCom_Email' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'depends' => array('FCom_Core'),
            'translations' => array('de' => 'de.csv'),
            'description' => "Base email subscription and mailing list management",
            'bootstrap' => array('file'=>'EmailFrontend.php', 'callback'=>'FCom_Email_Frontend::bootstrap'),
            'migrate' => 'FCom_Email_Migrate',
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'EmailAdmin.php', 'callback'=>'FCom_Email_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'EmailFrontend.php', 'callback'=>'FCom_Email_Frontend::bootstrap'),
                ),
            ),
        ),




    ),
);