<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
        $this->BLayout->view('prodreviews/products/tab')->model = $model;
    }

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'product_review' => BLocale::i()->_('Product Reviews'),
            'settings/Sellvana_ProductReviews' => BLocale::i()->_('Product Reviews Settings'),
        ]);
    }

    /**
     * @param array $args
     */
    public function onGetDashboardWidgets($args)
    {
        $view = $args['view'];
        $view->addWidget('latest-product-reviews', [
            'title' => 'Latest Product Reviews',
            'icon' => 'inbox',
            'view' => 'dashboard/latest-product-reviews',
            'cols' => 4,
            'async' => true,
            'filter' => false,
            'permission' => 'product_review'
        ]);
    }
}
