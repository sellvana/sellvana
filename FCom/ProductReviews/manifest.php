<?php return array(
    'modules' => array(
        // product reviews
        'FCom_ProductReviews' => array(
            'version' => '0.1.1',
            'root_dir' => '',
            'depends' => array('FCom_Catalog', 'FCom_Customer'),
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