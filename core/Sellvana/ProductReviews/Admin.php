<?php

/**
 * Class Sellvana_ProductReviews_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_ProductReviews_Admin extends BClass
{
    public function hookProductTab($args)
    {
        $model = $args['model'];
        $this->BLayout->getView('prodreviews/products/tab')->model = $model;
    }

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'product_review' => 'Product Reviews',
            'settings/Sellvana_ProductReviews' => 'Product Reviews Settings',
        ]);
    }
}
