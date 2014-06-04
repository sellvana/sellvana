<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Cms_Frontend extends BClass
{
    public function bootstrap()
    {
        $config = $this->BConfig->get('modules/FCom_Cms');
        if (!empty($config['page_enable'])) {
            $prefix = !empty($config['page_url_prefix']) ? $config['page_url_prefix'] . '/' : '';
            BRouting::i()->route('/' . $prefix . '*page', 'FCom_Cms_Frontend_Controller.page');
        }
        /*
        if (!empty($config['nav_enable'])) {
            $prefix = !empty($config['nav_url_prefix']) ? $config['nav_url_prefix'] . '/' : '';
            BRouting::i()->get('/' . $prefix . '*nav', 'FCom_Cms_Frontend_Controller.nav');
        }
        */
    }
}
