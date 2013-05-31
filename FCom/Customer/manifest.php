<?php return array(
    'modules' => array(
        // customer account and management
        'FCom_Customer' => array(
            'version' => '0.1.3',
            'require' => array('module'=>array('FCom_Core'=>'0.1.0', 'FCom_Geo'=>'0.1.0')),
            'translations' => array('de' => 'de.php'),
            'description' => "Customer Accounts and Management",
            "require" => array("module" => "FCom_Core"),
        ),
    ),
);