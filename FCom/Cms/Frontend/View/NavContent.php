<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Cms_Frontend_View_NavContent
 *
 * @property FCom_Cms_Model_Nav $FCom_Cms_Model_Nav
 */
class FCom_Cms_Frontend_View_NavContent extends FCom_Core_View_Abstract
{
    protected function _render()
    {
        $nav = $this->get('nav');
        if (is_numeric($nav)) {
            $nav = $this->FCom_Cms_Model_Nav->load($nav);
        } elseif (is_string($nav)) {
            $nav = $this->FCom_Cms_Model_Nav->load($nav, 'handle');
        }
        /** @var FCom_Cms_Model_Nav $nav */
        if (!$nav || !is_object($nav) || !$nav instanceof FCom_Cms_Model_Nav) {
            $this->BDebug->warning('CMS Nav node not found or invalid');
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
