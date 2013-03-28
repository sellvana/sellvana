<?php return array(
    'modules' => array(

        // cart, checkout and customer account views and controllers
        'FCom_CatalogIndex' => array(
            'version' => '0.1.5',
            'root_dir' => '',
            'migrate' => 'FCom_CatalogIndex_Migrate',
            'depends' => array('FCom_Catalog', 'FCom_CustomField'),
            //'translations' => array('de' => 'de.php'),
            'tests' => "FCom_CatalogIndex_Tests_AllTests",
            'bootstrap' => array('file'=>'CatalogIndex.php', 'callback'=>'FCom_CatalogIndex::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'CatalogIndexAdmin.php', 'callback'=>'FCom_CatalogIndex_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'CatalogIndexFrontend.php', 'callback'=>'FCom_CatalogIndex_Frontend::bootstrap'),
                ),
            ),
            'description' => "Catalog search and facets indexing engine",
        ),





    ),
);