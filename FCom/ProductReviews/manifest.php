<?php return array(
    'modules' => array(
        // product reviews
        'FCom_ProductReviews' => array(
            'version' => '0.1.5',
            'root_dir' => '',
            'depends' => array(
                'FCom_Catalog' => array('name' => 'FCom_Catalog', 'version'=>array('from'=>'0.1.0')),
                'FCom_Customer'),
            'translations' => array('de' => 'de.php'),
            'tests' => "FCom_ProductReviews_Tests_AllTests",
            'description' => "Product reviews by customers",
            'bootstrap' => array('file'=>'ProductReviewsFrontend.php', 'callback'=>'FCom_ProductReviews_Frontend::bootstrap'),
            'migrate' => 'FCom_ProductReviews_Migrate',
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'ProductReviewsAdmin.php', 'callback'=>'FCom_ProductReviews_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'ProductReviewsFrontend.php', 'callback'=>'FCom_ProductReviews_Frontend::bootstrap'),
                ),
            ),
        ),




    ),
);