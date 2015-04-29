<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Cms_Model_Nav
 *
 * @property Sellvana_Cms_Model_Page $Sellvana_Cms_Model_Page
 * @property FCom_Frontend_Main $FCom_Frontend_Main
 */

class Sellvana_Cms_Model_Nav extends FCom_Core_Model_TreeAbstract
{
    protected static $_table = 'fcom_cms_nav';
    protected static $_origClass = __CLASS__;
    protected static $_cacheAuto = true;

    public $_page;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['url_path'],
        'related'    => ['parent_id' => 'Sellvana_Cms_Model_Nav.id'],
    ];

    public function getUrl()
    {
        if ($this->url_href) {
            if (0 === stripos($this->url_href, ['http://', 'https://'])) {
                return $this->url_href;
            } else {
                return $this->FCom_Frontend_Main->href($this->url_href);
            }
        }
        $config = $this->BConfig->get('modules/Sellvana_Cms');
        $prefix = !empty($config['nav_url_prefix']) ? $config['nav_url_prefix'] . '/' : '';

        return $this->FCom_Frontend_Main->href($prefix . $this->url_path);

    }

    public function validateNav()
    {
        switch ($this->node_type) {
        case 'cms_page':
            $this->_page = $this->Sellvana_Cms_Model_Page->load($this->reference, 'handle');
            return $this->_page;

        default:
            return true;
        }
        return false;
    }

    public function render()
    {
        switch ($this->node_type) {
        case 'cms_page':
            $this->_page->render();
            break;

        case 'content':
        default:
            $this->BLayout
                ->addView('cms_nav', [
                    'renderer'    => 'FCom_LibTwig_Main::renderer',
                    'source'      => $this->content ? $this->content : ' ',
                    'source_name' => 'cms_nav:' . $this->url_path . ':' . strtotime($this->update_at),
                ])
                ->hookView('main', 'cms_nav')
            ;

            if (($root = $this->BLayout->view('root'))) {
                $root->addBodyClass('cms-' . str_replace('/', '-', $this->url_path))
                    ->addBodyClass('page-' . str_replace('/', '-', $this->url_path));
            }

            if (($head = $this->BLayout->view('head'))) {
                $head->title($this->full_name);
                foreach (['title', 'description', 'keywords'] as $f) {
                    if (($v = $this->get('meta_' . $f))) {
                        $head->meta($f, $v);
                    }
                }
            }

            if ($this->layout_update) {
                $layoutUpdate = $this->BUtil->fromJson($this->layout_update);
                if (!is_null($layoutUpdate)) {
                    $this->BLayout->addLayout('cms_nav', $layoutUpdate)->applyLayout('cms_nav');
                } else {
                    $this->BDebug->warning('Invalid layout update for CMS Nav');
                }
            }
            break;
        }
        return $this;
    }
}

