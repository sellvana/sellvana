<?php

class FCom_Cms_Admin_Controller_Blocks extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'cms/blocks';

    public function action_index()
    {
        $this->view('jqgrid')->config = array(
            'grid'=>array(
                'id' => 'cms_blocks',
                'url' => BApp::href('cms/blocks/grid_data'),
                'editurl' => BApp::href('cms/blocks/grid_data'),
                'columns' => array(
                    'id' => array('label'=>'ID'),
                    'handle' => array('label'=>'Handle', 'editable'=>true, 'formatter'=>'showlink', 'formatoptions'=>array(
                        'baseLinkUrl' => BApp::href('cms/blocks/form/'), 'idName' => 'id',
                    )),
                    'description' => array('label'=>'Description', 'editable'=>true),
                    'version' => array('label'=>'Version'),
                    'create_dt' => array('label'=>'Created', 'formatter'=>'date'),
                    'update_dt' => array('label'=>'Updated', 'formatter'=>'date'),
                ),
            ),
            'custom'=>array('personalize'=>true),
            'navGrid' => array('add'=>true, 'edit'=>true, 'del'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
        );
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
        $id = BRequest::i()->params('id', true);
        if ($id && !($model = FCom_Cms_Model_Block::i()->load($id))) {
            BDebug::error('Invalid Page ID: '.$id);
        }
        if (empty($model)) {
            $model = FCom_Cms_Model_Block::i()->create();
        }
        $view = $this->view('cms/blocks-form')->set('model', $model);
        $this->layout('/cms/blocks/form');
        $this->processFormTabs($view, $model, 'edit');
    }

    public function action_form__POST()
    {
        try {
            $id = BRequest::i()->params('id', true);
            if ($id) {
                $model = FCom_Cms_Model_Block::i()->load($id);
            } else {
                $model = FCom_Cms_Model_Block::i()->create();
            }
            $model->set(BRequest::i()->post('model'))->save();
            $id = $model->id;
            BSession::i()->addMessage('CMS Block Updated', 'success', 'admin');
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }
        BResponse::i()->redirect(BApp::href('cms/pages'));
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