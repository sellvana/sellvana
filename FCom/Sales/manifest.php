<?php return array(
    'modules' => array(

        'FCom_Sales' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'migrate' => 'FCom_Sales_Migrate',
            'tests' => "FCom_Sales_Tests_AllTests",
            'bootstrap' => array('file'=>'Sales.php', 'callback'=>'FCom_Sales::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'SalesAdmin.php', 'callback'=>'FCom_Sales_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'SalesFrontend.php', 'callback'=>'FCom_Sales_Frontend::bootstrap'),
                ),
            ),
            'description' => "Sales module",
        ),





    ),
);