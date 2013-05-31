<?php return array(
    'modules' => array(
        // catalog views and controllers
        'FCom_Catalog' => array(
            'version' => '0.2.1',
            'require' => array('module'=>array('FCom_Core'=>'0.1.0')),
            'categories' => array('Catalog', 'Products'),
            'translations' => array('de' => 'de.php'),
            'description' => "Categories and products management, admin and frontend",
        ),
    ),
);