<?php return array(
    'modules' => array(
        // catalog views and controllers
        'FCom_Catalog' => array(
            'version' => '0.2.0',
            'root_dir' => '',
            'depends' => array('FCom_Core'),
            'categories' => array('Catalog', 'Products'),
            'translations' => array('de' => 'de.php'),
            'tests' => "FCom_Catalog_Tests_AllTests",
            'description' => "Categories and products management, admin and frontend",
            'migrate' => 'FCom_Catalog_Migrate',
            //'bootstrap' => array('file'=>'CatalogFrontend.php', 'callback'=>'FCom_Catalog_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'CatalogAdmin.php', 'callback'=>'FCom_Catalog_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'CatalogFrontend.php', 'callback'=>'FCom_Catalog_Frontend::bootstrap'),
                ),
            ),
        ),



    ),
);