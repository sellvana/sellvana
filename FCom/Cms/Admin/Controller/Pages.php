<?php

class FCom_Cms_Admin_Controller_Pages extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/cms/pages');
    }

    public function action_grid_data()
    {
        $orm = FCom_Cms_Model_Page::i()->orm('p')->select('p.*');
        $data = FCom_Admin_View_Grid::i()->processORM($orm, __METHOD__);
        BResponse::i()->json($data);
    }

    public function action_grid_data__POST()
    {
        $this->_processGridDataPost('FCom_Cms_Model_Page');
    }

    public function action_form()
    {
        $id = BRequest::i()->params('id', true);
        $page = FCom_Cms_Model_Page::i()->load($id);
        $view = $this->view('cms/pages-form')->set('model', $page);
        $this->layout('/cms/pages/form');
        $this->processFormTabs($view, $page, 'edit');
    }

    public function action_form__POST()
    {
        try {
            $id = BRequest::i()->params('id', true);
            if ($id) {
                $page = FCom_Cms_Model_Page::i()->load($id);
            } else {
                $page = FCom_Cms_Model_Page::i()->create();
            }
            $page->set(BRequest::i()->post('model'))->save();
            $id = $page->id;
            BSession::i()->addMessage('CMS Page Updated', 'success', 'admin');
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }
        BResponse::i()->redirect(BApp::href('cms/pages'));
    }

    public function action_history_grid_data()
    {
        $orm = FCom_Cms_Model_PageHistory::i()->orm('ph')->select('ph.*');
        $data = FCom_Admin_View_Grid::i()->processORM($orm, __METHOD__);
        BResponse::i()->json($data);
    }

    public function action_history_grid_data__POST()
    {
        $this->_processGridDataPost('FCom_Cms_Model_PageHistory');
    }

}