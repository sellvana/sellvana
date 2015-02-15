<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Cms_Frontend
 *
 * @property FCom_Cms_Frontend_View_Block $FCom_Cms_Frontend_View_Block
 */
class FCom_Cms_Frontend extends BClass
{
    public function bootstrap()
    {
        $config = $this->BConfig->get('modules/FCom_Cms');
        if (!empty($config['page_enable'])) {
            $prefix = !empty($config['page_url_prefix']) ? $config['page_url_prefix'] . '/' : '';
            $this->BRouting->route('/' . $prefix . '*page', 'FCom_Cms_Frontend_Controller.page');
        }
        /*
        if (!empty($config['nav_enable'])) {
            $prefix = !empty($config['nav_url_prefix']) ? $config['nav_url_prefix'] . '/' : '';
            $this->BRouting->get('/' . $prefix . '*nav', 'FCom_Cms_Frontend_Controller.nav');
        }
        */

        $this->BLayout->addMetaDirective('cms_block', 'FCom_Cms_Frontend.metaDirectiveCmsBlockCallback');
    }

    public function onFrontendIndexBeforeDispatch($args)
    {
        if ($args['action'] !== 'index') {
            return;
        }
        $cmsPagesEnabled = $this->BConfig->get('modules/FCom_Cms/page_enable');
        $indexPage = $this->BConfig->get('modules/FCom_Cms/index_page');
        if (!$cmsPagesEnabled || !$indexPage) {
            return;
        }
        $args['controller']->forward('page', 'FCom_Cms_Frontend_Controller', ['block' => $indexPage]);
    }

    public function metaDirectiveCmsBlockCallback($d)
    {
        $params = [];
        if (!empty($d['view_name'])) {
            $params['view_name'] = $d['view_name'];
        }
        $this->FCom_Cms_Frontend_View_Block->createView($d['name'], $params);
    }
}
