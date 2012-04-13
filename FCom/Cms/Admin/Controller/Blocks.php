<?php

class FCom_Cms_Admin_Controller_Blocks extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_permission = 'cms/blocks';
    protected $_gridHref = 'cms/blocks';
    protected $_gridLayoutName = '/cms/blocks';
    protected $_formLayoutName = '/cms/blocks/form';
    protected $_formViewName = 'cms/blocks-form';
    protected $_modelClassName = 'FCom_Cms_Model_Block';
    protected $_mainTableAlias = 'b';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['grid']['columns'] += array(
            'handle' => array('label'=>'Handle', 'editable'=>true, 'formatter'=>'showlink', 'formatoptions'=>array(
                'baseLinkUrl' => BApp::href('cms/blocks/form/'), 'idName' => 'id',
            )),
            'description' => array('label'=>'Description', 'editable'=>true),
            'version' => array('label'=>'Version'),
            'create_dt' => array('label'=>'Created', 'formatter'=>'date'),
            'update_dt' => array('label'=>'Updated', 'formatter'=>'date'),
        );
        return $config;
    }

    public function action_history_grid_data()
    {
        $id = BRequest::i()->params('id', true);
        if (!$id) {
            $data = array();
        } else {
            $orm = FCom_Cms_Model_BlockHistory::i()->orm('bh')->select('bh.*')
                ->where('block_id', $id);
            $data = FCom_Admin_View_Grid::i()->processORM($orm, __METHOD__);
        }
        BResponse::i()->json($data);
    }

    public function action_history_grid_data__POST()
    {
        $this->_processGridDataPost('FCom_Cms_Model_BlockHistory');
    }
}