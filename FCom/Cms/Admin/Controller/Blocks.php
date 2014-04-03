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
        $config['columns'] = array(
            array('type' => 'row_select'),
            array('name' => 'handle', 'label'=>'Handle'),
            array('name' => 'description', 'label'=>'Description', 'editable'=>true),
            array('type' => 'input', 'name' => 'renderer', 'label'=>'Renderer',
                  'options' => BLayout::i()->getAllRenderers(true), 'editable' => true, 'mass-editable' => true, 'editor' => 'select'),
            array('name' => 'version', 'label'=>'Version'),
            array('type' => 'input', 'name' => 'page_enabled', 'label'=>'Page Enable',
                  'options' => array('1' => 'Yes', '0' => 'No'), 'editable' => true, 'mass-editable' => true, 'editor' => 'select'),
            array('name' => 'page_url', 'label'=>'Page Url'),
            array('name' => 'page_title', 'label'=>'Page Title'),
            array('name' => 'meta_title', 'label'=>'Meta Title', 'hidden' => true),
            array('name' => 'meta_description', 'label'=>'Meta Description', 'hidden' => true),
            array('name' => 'meta_keywords', 'label'=>'Meta Keywords', 'hidden' => true),
            array('name' => 'modified_time', 'label'=>'Modified Time', 'hidden' => true),
            array('type' => 'btn_group', 'buttons'=> array(
                array('name' => 'edit'),
                array('name' => 'delete'),
            )),
        );
        $config['actions'] = array(
            'edit' => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'handle', 'type' => 'text'),
            array('field' => 'page_enabled', 'type' => 'multiselect'),
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

    public function historyGridConfig($m)
    {
        return array(
            'grid'=>array(
                'id' => 'cms_blocks_form_history',
                'url' => BApp::href('cms/blocks/history/'.$m->id.'/grid_data'),
                'editurl' => BApp::href('cms/blocks/history/'.$m->id.'/grid_data'),
                'columns' => array(
                    'id' => array('label'=>'ID', 'hidden'=>true),
                    'ts' => array('label'=>'TimeStamp', 'formatter'=>'date'),
                    'version' => array('label'=>'Version'),
                    'user_id' => array('type'=>'input', 'label'=>'User','editor'=>'select', 'options'=>FCom_Admin_Model_User::i()->options()),
                    'username' => array('Label'=>'User Name', 'hidden'=>true),
                    'comments' => array('labl'=>'Comments'),
                ),
            ),
            'custom'=>array('personalize'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
        );
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
