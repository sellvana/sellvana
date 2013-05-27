<?php return array(
    'modules' => array(
        'FCom_Geo' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'migrate' => 'FCom_Geo_Migrate',
            'bootstrap' => array('file'=>'Geo.php', 'callback'=>'FCom_Geo::bootstrap'),
            'require' => array('module'=>array('FCom_Core'=>'0.1.0')),
            'description' => "Geographic information about countries and states",
        ),
    ),
);