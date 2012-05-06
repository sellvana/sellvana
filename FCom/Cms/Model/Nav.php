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

    public static function install()
    {
        $tNav = static::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$tNav} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `id_path` varchar(100) NOT NULL,
  `node_name` varchar(255) NOT NULL,
  `full_name` text NOT NULL,
  `url_key` varchar(255) NOT NULL,
  `url_path` varchar(255) NOT NULL,
  `url_href` varchar(255) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL,
  `num_children` int(10) unsigned DEFAULT NULL,
  `num_descendants` int(10) unsigned DEFAULT NULL,
  `node_type` varchar(20) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `contents` text,
  `layout_update` text,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_{$tNav}_parent` FOREIGN KEY (`parent_id`) REFERENCES {$tNav} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }
}

