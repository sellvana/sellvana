<?php return array(
    'modules' => array(
        // catalog views and controllers
        'FCom_CustomField' => array(
            'version' => '0.1.4',
            'require' => array('module'=>array('FCom_Catalog'=>'0.2.1')),
            'translations' => array('de' => 'de.php'),
            'after' => array('FCom_Customer'),
            'description' => "Base custom fields implementation, currently for catalog only",
        ),
    ),
);