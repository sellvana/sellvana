<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_AdminLiveFeed_Main extends BCLass
{
    public function onGetHeaderNotifications()
    {
        if ($this->BModuleRegistry->isLoaded('FCom_PushServer')) {
            $this->FCom_PushServer_Model_Client->sessionClient()->subscribe('activities_feed');
        }
    }

    public function onProductAfterSave($args)
    {
        if ($args['model']->isNewRecord()) {
            if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_catalog')) {
                $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
                        'href' => 'catalog/products/form?id=' . $args['model']->get('id'),
                        'text' => $this->BLocale->_('New %s of products have been added to catalog', '#' . $args['model']->get('id')),
                    ]);
            }
        }
    }

    public function onPrefAfterSave($args)
    {
        if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_newsletter')) {
            $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
                'text' =>$args['model']->get('email') . ' ' . $this->BLocale->_('has subscribed to newsletter'),
            ]);
        }
    }
}
