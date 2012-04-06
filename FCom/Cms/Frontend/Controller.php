<?php

class FCom_Cms_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('base');

        $handle = BRequest::i()->params('page');
        $page = FCom_Cms_Model_Page::i()->load($handle, 'handle');
        if (!$page) {
            $this->forward('noroute');
            return;
        }

        $page->render();
    }
}