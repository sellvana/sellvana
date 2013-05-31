<?php return array(
    'modules' => array(
        'FCom_MultiSite' => array(
            'version' => '0.1.0',
            'translations' => array('de' => 'de.php'),
            'description' => "Enable multiple website management from the same instance",
            'before_bootstrap' => array('callback'=>'FCom_MultiSite_Main::beforeBootstrap'),
            "require" => array("module" => array("FCom_Core" => "0.1.0")),
        ),
    ),
);