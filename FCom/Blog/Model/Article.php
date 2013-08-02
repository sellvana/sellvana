<?php

class FCom_Blog_Model_Article extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_blog_article';
    static protected $_origClass = __CLASS__;

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('create_at', BDb::now(), null);
        $this->set('update_at', BDb::now());

        if (!$this->url_key) {
            $this->url_key = BLocale::transliterate($this->title);
        }

        if (!$this->author_user_id) {
            $this->author_user_id = FCom_Admin_Model_User::i()->sessionUserId();
        }

        return true;
    }
}
