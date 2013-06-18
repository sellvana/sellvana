<?php

class FCom_Cms_Admin_Controller_Pages extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'cms/pages';
    protected $_modelClass = 'FCom_Cms_Model_Page';
    protected $_gridTitle = 'CMS Pages';
    protected $_recordName = 'CMS Page';

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
        return $config;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'title' => $m->id ? 'Edit CMS Page: '.$m->title : 'Create New CMS Page',
        ));
    }

    public function historyGridConfig($m)
    {
        return array(
            'grid'=>array(
                'id' => 'cms_pages_form_history',
                'url' => BApp::href('cms/pages/history/'.$m->id.'/grid_data'),
                'editurl' => BApp::href('cms/pages/history/'.$m->id.'/grid_data'),
                'columns' => array(
                    'id' => array('label'=>'ID', 'hidden'=>true),
                    'ts' => array('label'=>'TimeStamp', 'formatter'=>'date'),
                    'version' => array('label'=>'Version'),
                    'user_id' => array('label'=>'User', 'options'=>FCom_Admin_Model_User::i()->options()),
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