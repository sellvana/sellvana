<?php

class FCom_ProductReviews_Admin extends BClass
{
    public function hookProductTab($args)
    {
        $model = $args['model'];
        BLayout::i()->view('prodreviews/products/tab')->model = $model;
    }

    static public function bootstrap()
    {

        FCom_Admin_Model_Role::i()->createPermission(array(
            'product_review' => 'Product Reviews',
        ));
    }
}
