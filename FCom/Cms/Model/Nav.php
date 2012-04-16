<?php

class FCom_Cms_Model_Nav extends FCom_Core_Model_TreeAbstract
{
    protected static $_table = 'fcom_cms_nav';
    protected static $_origClass = __CLASS__;
    protected static $_cacheAuto = true;

    public $_page;

    public function validate()
    {
        switch ($this->node_type) {
        case 'cms_page':
            $this->_page = FCom_Cms_Model_Page::i()->load($this->reference, 'handle');
            return !!$this->_page;

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
                ->addView('cms_nav', array(
                    'renderer'    => 'BPHPTAL::renderer',
                    'source'      => $this->content ? $this->content : ' ',
                    'source_name' => 'cms_nav:'.$this->url_path.':'.strtotime($this->update_dt),
                ))
                ->hookView('main', 'cms_nav')
            ;

            if (($root = BLayout::i()->view('root'))) {
                $root->addBodyClass('cms-'.str_replace('/', '-', $this->url_path))
                    ->addBodyClass('page-'.str_replace('/', '-', $this->url_path));
            }

            if (($head = BLayout::i()->view('head'))) {
                $head->title($this->full_name);
                foreach (explode(',', 'title,description,keywords') as $f) {
                    if (($v = $this->get('meta_'.$f))) {
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

