<?php

class FCom_Cms_Model_Nav extends FCom_Core_Model_TreeAbstract
{
    protected static $_table = 'fcom_cms_nav';
    protected static $_origClass = __CLASS__;
    protected static $_cacheAuto = true;

    public $_page;

    public function getUrl()
    {
        if ($this->url_href) {
            if (0 === stripos($this->url_href, ['http://', 'https://'])) {
                return $this->url_href;
            } else {
                return FCom_Frontend_Main::i()->href($this->url_href);
            }
        }
        $config = BConfig::i()->get('modules/FCom_Cms');
        $prefix = !empty($config['nav_url_prefix']) ? $config['nav_url_prefix'] . '/' : '';

        return FCom_Frontend_Main::i()->href($prefix . $this->url_path);

    }

    public function validateNav()
    {
        switch ($this->node_type) {
        case 'cms_page':
            $this->_page = FCom_Cms_Model_Page::i()->load($this->reference, 'handle');
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
            BLayout::i()
                ->addView('cms_nav', [
                    'renderer'    => 'FCom_LibTwig_Main::renderer',
                    'source'      => $this->content ? $this->content : ' ',
                    'source_name' => 'cms_nav:' . $this->url_path . ':' . strtotime($this->update_at),
                ])
                ->hookView('main', 'cms_nav')
            ;

            if (($root = BLayout::i()->view('root'))) {
                $root->addBodyClass('cms-' . str_replace('/', '-', $this->url_path))
                    ->addBodyClass('page-' . str_replace('/', '-', $this->url_path));
            }

            if (($head = BLayout::i()->view('head'))) {
                $head->title($this->full_name);
                foreach (explode(',', 'title,description,keywords') as $f) {
                    if (($v = $this->get('meta_' . $f))) {
                        $head->meta($f, $v);
                    }
                }
            }

            if ($this->layout_update) {
                $layoutUpdate = BUtil::fromJson($this->layout_update);
                if (!is_null($layoutUpdate)) {
                    BLayout::i()->addLayout('cms_nav', $layoutUpdate)->applyLayout('cms_nav');
                } else {
                    BDebug::warning('Invalid layout update for CMS Nav');
                }
            }
            break;
        }
        return $this;
    }
}

