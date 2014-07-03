<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ProductReviews_Admin extends BClass
{
    public function hookProductTab($args)
    {
        $model = $args['model'];
        $this->BLayout->view('prodreviews/products/tab')->model = $model;
    }

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'product_review' => 'Product Reviews',
        ]);
    }

    public function onGetHeaderNotifications($args)
    {
        if ($this->BModuleRegistry->isLoaded('FCom_PushServer')
            && $this->BConfig->get('modules/FCom_ProductReviews/newreview_realtime_notification')
        ) {
            $this->FCom_PushServer_Model_Client->sessionClient()->subscribe('reviews_feed');
        }
    }
}
