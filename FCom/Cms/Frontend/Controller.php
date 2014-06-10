<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Cms_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_page()
    {
        $pageUrl = $this->BRequest->param('page');
        if ($pageUrl === '' || is_null($pageUrl)) {
            $this->forward(false);
            return;
        }
        $block = $this->FCom_Cms_Model_Block->loadWhere(['page_enabled' => 1, 'page_url' => (string)$pageUrl]);
        if (!$block || !$block->validateBlock()) {
            $this->forward(false);
            return;
        }

        $this->layout('cms_page');

        $view = $this->FCom_Cms_Frontend_View_Block->createView($block);
        $this->BLayout->hookView('main', $view->param('view_name'));

        if (($root = $this->BLayout->view('root'))) {
            $root->addBodyClass('cms-' . $block->handle)
                ->addBodyClass('page-' . $block->handle);
        }

        if (($head = $this->BLayout->view('head'))) {
            $head->addTitle($block->page_title);
            foreach (explode(',', 'title,description,keywords') as $f) {
                if (($v = $block->get('meta_' . $f))) {
                    $head->meta($f, $v);
                }
            }
        }

        if ($block->layout_update) {
            $layoutUpdate = $this->BYAML->parse($block->layout_update);
            if (!is_null($layoutUpdate)) {
                $this->BLayout->addLayout('cms_page', $layoutUpdate)->applyLayout('cms_page');
            } else {
                $this->BDebug->warning('Invalid layout update for CMS page');
            }
        }
    }

    public function action_nav()
    {
        $handle = $this->BRequest->params('nav');
        $nav = $this->FCom_Cms_Model_Nav->load($handle, 'url_path');
        if (!$nav || !$nav->validateNav()) {
            $this->forward(false);
            return;
        }

        $this->layout('cms_nav');

        $this->BLayout->view('cms/nav-content')->set('nav', $nav);

        if (($root = $this->BLayout->view('root'))) {
            $htmlClass = $this->BUtil->simplifyString($nav->url_path);
            $root->addBodyClass('cms-' . $htmlClass)
                ->addBodyClass('page-' . $htmlClass);
        }

        if (($head = $this->BLayout->view('head'))) {
            $head->addTitle($nav->title);
            foreach (explode(',', 'title,description,keywords') as $f) {
                if (($v = $nav->get('meta_' . $f))) {
                    $head->meta($f, $v);
                }
            }
        }

        if ($nav->layout_update) {
            $layoutUpdate = $this->BYAML->parse($nav->layout_update);
            if (!is_null($layoutUpdate)) {
                $this->BLayout->addLayout('cms_nav', $layoutUpdate)->applyLayout('cms_nav');
            } else {
                $this->BDebug->warning('Invalid layout update for CMS nav node');
            }
        }
    }
}
