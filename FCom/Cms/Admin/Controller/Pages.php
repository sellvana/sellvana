<?php

class FCom_Cms_Admin_Controller_Pages extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/cms/pages');
    }

    public function action_grid_data()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')->select('p.*');
        $data = FCom_Admin_View_Grid::i()->processORM($orm, __METHOD__);
        BResponse::i()->json($data);
    }

    public function action_grid_data__POST()
    {

    }

    public function action_form()
    {
        $this->layout('/cms/pages/form');
    }

    public function action_form__POST()
    {

    }
}