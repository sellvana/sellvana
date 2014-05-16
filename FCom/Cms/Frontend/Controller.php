<?php

class FCom_Cms_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_page()
    {
        $pageUrl = BRequest::i()->params('page');
        if ($pageUrl === '' || is_null($pageUrl)) {
            $this->forward(false);
            return;
        }
        $block = FCom_Cms_Model_Block::i()->load(['page_enabled' => 1, 'page_url' => $pageUrl]);
        if (!$block || !$block->validateBlock()) {
            $this->forward(false);
            return;
        }

        $this->layout('cms_page');

        $view = FCom_Cms_Frontend_View_Block::i()->createView($block);
        BLayout::i()->hookView('main', $view->param('view_name'));

        if (($root = BLayout::i()->view('root'))) {
            $root->addBodyClass('cms-' . $block->handle)
                ->addBodyClass('page-' . $block->handle);
        }

        if (($head = BLayout::i()->view('head'))) {
            $head->addTitle($block->page_title);
            foreach (explode(',', 'title,description,keywords') as $f) {
                if (($v = $block->get('meta_' . $f))) {
                    $head->meta($f, $v);
                }
            }
        }

        if ($block->layout_update) {
            $layoutUpdate = BYAML::parse($block->layout_update);
            if (!is_null($layoutUpdate)) {
                BLayout::i()->addLayout('cms_page', $layoutUpdate)->applyLayout('cms_page');
            } else {
                BDebug::warning('Invalid layout update for CMS page');
            }
        }
    }

    public function action_nav()
    {
        $handle = BRequest::i()->params('nav');
        $nav = FCom_Cms_Model_Nav::i()->load($handle, 'url_path');
        if (!$nav || !$nav->validateNav()) {
            $this->forward(false);
            return;
        }

        $this->layout('cms_nav');

        BLayout::i()->view('cms/nav-content')->set('nav', $nav);

        if (($root = BLayout::i()->view('root'))) {
            $htmlClass = BUtil::simplifyString($nav->url_path);
            $root->addBodyClass('cms-' . $htmlClass)
                ->addBodyClass('page-' . $htmlClass);
        }

        if (($head = BLayout::i()->view('head'))) {
            $head->addTitle($nav->title);
            foreach (explode(',', 'title,description,keywords') as $f) {
                if (($v = $nav->get('meta_' . $f))) {
                    $head->meta($f, $v);
                }
            }
        }

        if ($nav->layout_update) {
            $layoutUpdate = BYAML::parse($nav->layout_update);
            if (!is_null($layoutUpdate)) {
                BLayout::i()->addLayout('cms_nav', $layoutUpdate)->applyLayout('cms_nav');
            } else {
                BDebug::warning('Invalid layout update for CMS nav node');
            }
        }
    }
}
