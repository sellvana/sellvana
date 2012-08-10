<?php return array(
    'modules' => array(

        // Geographic information about countries and states
        'FCom_Geo' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'migrate' => 'FCom_Geo_Migrate',
            'bootstrap' => array('file'=>'Geo.php', 'callback'=>'FCom_Geo::bootstrap'),
            'depends' => array('FCom_Core'),
            'description' => "Geographic information about countries and states",
        ),





    ),
);