<?php

class FCom_Admin_Controller_HeaderSearch extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_gridPageViewName = 'header_search';
    protected $_gridLayoutName = '/header_search';

    public function action_index()
    {
        if (BRequest::i()->xhr()) {
            return ;
        }
        $result = array();
        $priority = 1000000;
        $url = '';
        BEvents::i()->fire(__METHOD__, array('result' => &$result));
        foreach ($result as $key) {
            if (is_array($key)) {
                if (isset($key['priority']) && $key['url'] && $key['priority'] < $priority) {
                    $priority = $key['priority'];
                    $url = $key['url'];
                }
            }
        }
        if ($url != '') {
            BResponse::i()->redirect($url);
        }

        if (($head = $this->view('head'))) {
            $head->addTitle($this->_gridTitle);
        }

        if (($nav = $this->view('admin/nav'))) {
            $nav->setNav($this->_navPath);
        }

        $pageView = $this->view($this->_gridPageViewName);
        $view = $this->gridView();
        $this->gridViewBefore(array('view' => $view, 'page_view' => $pageView));

        $this->layout();
        $this->_useDefaultLayout = false;
        if ($this->_useDefaultLayout) {
            BLayout::i()->applyLayout('default_grid');
        }
        BLayout::i()->applyLayout($this->_gridLayoutName);
    }
}
