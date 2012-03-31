<?php

class FCom_Cms_Admin_Controller_Blocks extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/cms/blocks');
    }

    public function action_grid_data()
    {
        $orm = FCom_Cms_Model_Block::i()->orm('b')->select('b.*');
        $data = FCom_Admin_View_Grid::i()->processORM($orm, __METHOD__);
        BResponse::i()->json($data);
    }

    public function action_grid_data__POST()
    {
        $this->_processGridDataPost('FCom_Cms_Model_Block');
    }

    public function action_form()
    {
        $this->layout('/cms/blocks/form');
    }

    public function action_form__POST()
    {

    }
}