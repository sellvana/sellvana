<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Cms_Frontend_View_NavContent extends FCom_Core_View_Abstract
{
    protected function _render()
    {
        $nav = $this->get('nav');
        if (is_numeric($nav)) {
            $nav = FCom_Cms_Model_Nav::i()->load($nav);
        } elseif (is_string($nav)) {
            $nav = FCom_Cms_Model_Nav::i()->load($nav, 'handle');
        }
        if (!$nav || !is_object($nav) || !$nav instanceof FCom_Cms_Model_Nav) {
            BDebug::warning('CMS Nav node not found or invalid');
            return '';
        }

        $this->setParam([
            'renderer'    => $nav->renderer ? $nav->renderer : 'FCom_LibTwig_Main::renderer',
            'source'      => $nav->content ? $nav->content : ' ',
            'source_name' => 'cms_nav:' . $nav->url_path . ':' . strtotime($nav->update_at),
        ]);

        return parent::_render();
    }
}
