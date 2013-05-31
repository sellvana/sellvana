<?php return array(
    'modules' => array(
        // cart, checkout and customer account views and controllers
        'FCom_CatalogIndex' => array(
            'version' => '0.1.6',
            'require' => array('module'=>array('FCom_Catalog'=>'0.2.1', 'FCom_CustomField'=>'0.1.4')),
            //'translations' => array('de' => 'de.php'),
            'description' => "Catalog search and facets indexing engine",
        ),
    ),
);