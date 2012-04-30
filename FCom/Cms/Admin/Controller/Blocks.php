<?php

class FCom_Cms_Admin_Controller_Blocks extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'cms/blocks';
    protected $_modelClass = 'FCom_Cms_Model_Block';
    protected $_gridTitle = 'CMS Block';
    protected $_recordName = 'CMS Block';

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

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'title' => $m->id ? 'Edit CMS Block: '.$m->handle : 'Create New CMS Block',
        ));
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