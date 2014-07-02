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
        if ($this->BApp->m('FCom_PushServer')->run_status === BModule::LOADED
            && $this->BConfig->get('modules/FCom_PushServer/newreview_realtime_notification')
        ) {
            $this->FCom_PushServer_Model_Client->sessionClient()->subscribe('reviews_feed');
        }
    }
}
