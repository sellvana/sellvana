<?php

class FCom_Cms_Admin_Controller_Pages extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_permission = 'cms/pages';
    protected $_gridHref = 'cms/pages';
    protected $_gridLayoutName = '/cms/pages';
    protected $_formLayoutName = '/cms/pages/form';
    protected $_formViewName = 'cms/pages-form';
    protected $_modelClassName = 'FCom_Cms_Model_Page';
    protected $_mainTableAlias = 'p';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['grid']['columns'] += array(
            'handle' => array('label'=>'Handle'),
            'title' => array('label'=>'Title'),
            'version' => array('label'=>'Version'),
            'create_dt' => array('label'=>'Created', 'formatter'=>'date'),
            'update_dt' => array('label'=>'Updated', 'formatter'=>'date'),
        );
        $config['custom']['dblClickHref'] = BApp::href('cms/pages/form?id=');
        return $config;
    }

    public function action_history_grid_data()
    {
        $id = BRequest::i()->params('id', true);
        if (!$id) {
            $data = array();
        } else {
            $orm = FCom_Cms_Model_PageHistory::i()->orm('ph')->select('ph.*')
                ->where('page_id', $id);
            $data = FCom_Admin_View_Grid::i()->processORM($orm, __METHOD__);
        }
        BResponse::i()->json($data);
    }

    public function action_history_grid_data__POST()
    {
        $this->_processGridDataPost('FCom_Cms_Model_PageHistory');
    }

}