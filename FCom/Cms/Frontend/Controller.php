<?php

class FCom_Cms_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_page()
    {
        $handle = BRequest::i()->params('page');
        $page = FCom_Cms_Model_Page::i()->load($handle, 'handle');
        if (!$page || !$page->validate()) {
            $this->forward(true);
            return;
        }
        $this->layout('base');
        $page->render();
    }

    public function action_nav()
    {
        $handle = BRequest::i()->params('nav');
        $nav = FCom_Cms_Model_Nav::i()->load($handle, 'url_path');
        if (!$nav || !$nav->validate()) {
            $this->forward(true);
            return;
        }
        $this->layout('base');
        $nav->render();
    }
}