<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Cms_Frontend
 *
 * @property Sellvana_Cms_Frontend_View_Block $Sellvana_Cms_Frontend_View_Block
 */
class Sellvana_Cms_Frontend extends BClass
{
    public function bootstrap()
    {
        $config = $this->BConfig->get('modules/Sellvana_Cms');
        if (!empty($config['page_enable'])) {
            $prefix = !empty($config['page_url_prefix']) ? $config['page_url_prefix'] . '/' : '';
            $this->BRouting->route('/' . $prefix . '*page', 'Sellvana_Cms_Frontend_Controller.page');
        }
        /*
        if (!empty($config['nav_enable'])) {
            $prefix = !empty($config['nav_url_prefix']) ? $config['nav_url_prefix'] . '/' : '';
            $this->BRouting->get('/' . $prefix . '*nav', 'Sellvana_Cms_Frontend_Controller.nav');
        }
        */

        $this->BLayout->addMetaDirective('cms_block', 'Sellvana_Cms_Frontend.metaDirectiveCmsBlockCallback');
    }

    public function onFrontendIndexBeforeDispatch($args)
    {
        if ($args['action'] !== 'index') {
            return;
        }
        $cmsPagesEnabled = $this->BConfig->get('modules/Sellvana_Cms/page_enable');
        $indexPage = $this->BConfig->get('modules/Sellvana_Cms/index_page');
        if (!$cmsPagesEnabled || !$indexPage) {
            return;
        }
        $args['controller']->forward('page', 'Sellvana_Cms_Frontend_Controller', ['block' => $indexPage]);
    }

    public function metaDirectiveCmsBlockCallback($d)
    {
        $params = [];
        if (!empty($d['view_name'])) {
            $params['view_name'] = $d['view_name'];
        }
        $this->Sellvana_Cms_Frontend_View_Block->createView($d['name'], $params);
    }
}
