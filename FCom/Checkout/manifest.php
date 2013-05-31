<?php return array(
    'modules' => array(

        // cart, checkout and customer account views and controllers
        'FCom_Checkout' => array(
            'version' => '0.2.1',
            'require' => array('module'=>array('FCom_Catalog'=>'0.2.1', 'FCom_Geo'=>'0.1.0', 'FCom_Sales'=>'0.1.10')),
            'translations' => array('de' => 'de.php'),
            'description' => "Base cart and checkout functionality",
        ),
    ),
);