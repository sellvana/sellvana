<?php

abstract class FCom_Admin_Controller_Abstract_GridForm extends FCom_Admin_Controller_Abstract
{
//    protected $_origClass = __CLASS__;
//    protected $_gridHref = 'base/grid/path';
//    protected $_gridLayoutName = '/layout/name';
//    protected $_formLayoutName = '/layout/name/form';
//    protected $_formViewName = 'view/name/form';
//    protected $_modelClassName = 'Model_Class_name';
//    protected $_mainTableAlias = 'm';

    public function gridConfig()
    {
        return array(
            'grid'=>array(
                'id' => 'sample_grid_id',
                'url' => BApp::href($this->_gridHref.'/grid_data'),
                'editurl' => BApp::href($this->_gridHref.'/grid_data'),
                'columns' => array(
                    'id' => array('label'=>'ID', 'formatter'=>'showlink', 'formatoptions'=>array(
                        'baseLinkUrl' => BApp::href($this->_gridHref.'/form/'), 'idName' => 'id',
                    )),
                ),
            ),
            'custom'=>array('personalize'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
        );
    }

    public function action_index()
    {
        $this->view('jqgrid')->config = $this->gridConfig();
        $this->layout($this->_gridLayoutName);
    }

    public function action_grid_data()
    {
        $class = $this->_modelClassName;
        $orm = $class::i()->orm($this->_mainTableAlias)->select($this->_mainTableAlias.'.*');
        $data = FCom_Admin_View_Grid::i()->processORM($orm, get_class($this).'::action_grid_data');
        BResponse::i()->json($data);
    }

    public function action_grid_data__POST()
    {
        $this->_processGridDataPost($this->_modelClassName);
    }

    public function action_form()
    {
        $class = $this->_modelClassName;
        $id = BRequest::i()->params('id', true);
        if ($id && !($model = $class::i()->load($id))) {
            BDebug::error('Invalid ID: '.$id);
        }
        if (empty($model)) {
            $model = $class::i()->create();
        }
        $view = $this->view($this->_formViewName)->set('model', $model);
        $this->layout($this->_formLayoutName);
        $this->processFormTabs($view, $model, 'edit');
    }

    public function action_form__POST()
    {
        try {
            $class = $this->_modelClassName;
            $id = BRequest::i()->params('id', true);
            $model = $id ? $class::i()->load($id) : $class::i()->create();
            $model->set(BRequest::i()->post('model'))->save();
            $id = $model->id;
            BSession::i()->addMessage('Changes have been saved', 'success', 'admin');
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }
        BResponse::i()->redirect(BApp::href($this->_gridHref));
    }
}