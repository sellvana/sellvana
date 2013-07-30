<?php

class FCom_Blog_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/blog/index');
    }

    public function action_tag()
    {
        $this->layout('/blog/tag');
    }

    public function action_archive()
    {
        $this->layout('/blog/archive');
    }

    public function action_article()
    {
        $this->layout('/blog/article');
    }
}
