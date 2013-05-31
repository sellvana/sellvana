<?php return array(
    'modules' => array(
        // product reviews
        'FCom_ProductReviews' => array(
            'version' => '0.1.5',
            'require' => array('module'=>array('FCom_Catalog' => '0.1.0', 'FCom_Customer'=>'0.1.3')),
            'translations' => array('de' => 'de.php'),
            'description' => "Product reviews by customers",
        ),
    ),
);