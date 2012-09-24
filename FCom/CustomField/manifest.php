<?php return array(
    'modules' => array(
        // catalog views and controllers
        'FCom_CustomField' => array(
            'version' => '0.1.4',
            'root_dir' => '',
            'bootstrap' => array('file'=>'CustomField.php', 'callback'=>'FCom_CustomField::bootstrap'),
            'tests' => "FCom_CustomField_Tests_AllTests",
            'depends' => array('FCom_Catalog'),
            'translations' => array('de' => 'de.php'),
            'after' => array('FCom_Customer'),
            'description' => "Base custom fields implementation, currently for catalog only",
            'migrate' => 'FCom_CustomField_Migrate',
            'bootstrap' => array('file'=>'CustomFieldFrontend.php', 'callback'=>'FCom_CustomField_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'CustomFieldAdmin.php', 'callback'=>'FCom_CustomField_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'CustomFieldFrontend.php', 'callback'=>'FCom_CustomField_Frontend::bootstrap'),
                ),
            ),
        ),



    ),
);