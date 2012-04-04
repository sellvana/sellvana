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

        BLayout::i()
            ->addView('cms_page', array(
                'renderer'    => 'BPHPTAL::renderer',
                'source'      => $page->content,
                'source_name' => 'cms_page:'.$page->handle.':'.strtotime($page->update_dt),
            ))
            ->hookView('main', 'cms_page')
        ;

        $root = BLayout::i()->view('root');
        $root->addBodyClass('cms-'.$page->handle)->addBodyClass('page-'.$page->handle);

        $head = BLayout::i()->view('head');
        $head->title($page->title);
        foreach (explode(',', 'title,description,keywords') as $f) {
            if (($v = $page->get('meta_'.$f))) {
                $head->meta($f, $v);
            }
        }

        if ($page->layout_update) {
            $layoutUpdate = BUtil::fromJson($page->layout_update);
            if (!is_null($layoutUpdate)) {
                BLayout::i()->addLayout('cms_page', $layoutUpdate)->applyLayout('cms_page');
            } else {
                BDebug::warning('Invalid layout update for CMS page');
            }
        }
    }
}