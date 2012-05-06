<?php

class FCom_Cms_Model_Page extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_page';
    protected static $_origClass = __CLASS__;

    public function validate()
    {
        return true;
    }

    public function render()
    {
        BLayout::i()
            ->addView('cms_page', array(
                'renderer'    => 'BPHPTAL::renderer',
                'source'      => $this->content ? $this->content : ' ',
                'source_name' => 'cms_page:'.$this->handle.':'.strtotime($this->update_dt),
            ))
            ->hookView('main', 'cms_page')
        ;

        if (($root = BLayout::i()->view('root'))) {
            $root->addBodyClass('cms-'.$this->handle)
                ->addBodyClass('page-'.$this->handle);
        }

        if (($head = BLayout::i()->view('head'))) {
            $head->title($this->title);
            foreach (explode(',', 'title,description,keywords') as $f) {
                if (($v = $this->get('meta_'.$f))) {
                    $head->meta($f, $v);
                }
            }
        }

        if ($this->layout_update) {
            $layoutUpdate = BUtil::fromJson($this->layout_update);
            if (!is_null($layoutUpdate)) {
                BLayout::i()->addLayout('cms_page', $layoutUpdate)->applyLayout('cms_page');
            } else {
                BDebug::warning('Invalid layout update for CMS page');
            }
        }
        return $this;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;

        if (!$this->get('create_dt')) {
            $this->set('create_dt', BDb::now());
        }
        $this->set('update_dt', BDb::now());
        $this->add('version');
        return true;
    }

    public function afterSave()
    {
        parent::afterSave();

        $user = FCom_Admin_Model_User::i()->sessionUser();
        $hist = FCom_Cms_Model_PageHistory::i()->create(array(
            'page_id' => $this->id,
            'user_id' => $user ? $user->id : null,
            'username' => $user ? $user->username : null,
            'version' => $this->version,
            'comments' => $this->version_comments,
            'ts' => BDb::now(),
            'data' => BUtil::toJson(BUtil::arrayMask($this->as_array(),
                'handle,title,content,layout_update,meta_title,meta_description,meta_keywords')),
        ))->save();
    }

    public static function install()
    {
        $tPage = static::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$tPage} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `layout_update` text COLLATE utf8_unicode_ci,
  `create_dt` datetime DEFAULT NULL,
  `update_dt` datetime DEFAULT NULL,
  `version` int(11) unsigned NOT NULL,
  `meta_title` text COLLATE utf8_unicode_ci,
  `meta_description` text COLLATE utf8_unicode_ci,
  `meta_keywords` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        ");
    }
}
