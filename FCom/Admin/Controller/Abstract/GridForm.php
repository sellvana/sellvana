<?php

abstract class FCom_Admin_Controller_Abstract_GridForm extends FCom_Admin_Controller_Abstract
{
    // Required parameters
    protected $_modelClass;# = 'Model_Class_Name';
    protected $_gridHref;# = 'feature';

    // Optional parameters
    protected $_permission;# = 'feature/permission';
    protected $_recordName = 'Record';
    protected $_gridTitle = 'List of Records';
    protected $_gridViewName = 'admin/grid';
    protected $_gridLayoutName;# = '/feature';
    protected $_formHref;# = 'feature/form';
    protected $_formLayoutName;# = '/feature/form';
    protected $_formViewName = 'admin/form';
    protected $_mainTableAlias = 'main';

    public function __construct()
    {
        parent::__construct();
        $this->_gridHref = trim($this->_gridHref, '/');
        if (is_null($this->_permission))     $this->_permission = $this->_gridHref;
        if (is_null($this->_gridLayoutName)) $this->_gridLayoutName = '/'.$this->_gridHref;
        if (is_null($this->_formHref))       $this->_formHref = $this->_gridHref.'/form';
        if (is_null($this->_formLayoutName)) $this->_formLayoutName = $this->_gridLayoutName.'/form';
        if (is_null($this->_gridViewName))   $this->_formViewName = 'admin/grid';
        if (is_null($this->_formViewName))   $this->_formViewName = 'admin/form';
        if (is_null($this->_mainTableAlias)) $this->_mainTableAlias = 'main';
    }

    public function gridConfig()
    {
        $gridDataUrl = BApp::href($this->_gridHref.'/grid_data');
        $formUrl = BApp::href($this->_formHref);
        $config = array(
            'grid'=>array(
                'id' => static::$_origClass,
                'url' => $gridDataUrl,
                'editurl' => $gridDataUrl,
                'columns' => array(
                    'id' => array('label'=>'ID', 'formatter'=>'showlink', 'formatoptions'=>array(
                        'baseLinkUrl' => $formUrl, 'idName' => 'id',
                    ), 'width'=>50),
                ),
                'toppager' => true,
            ),
            'custom'=>array('personalize'=>true, 'autoresize'=>true, 'hashState'=>true, 'export'=>true, 'dblClickHref'=>$formUrl.'?id='),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
        );
        BPubSub::i()->fire(static::$_origClass.'::gridConfig', array('config'=>&$config));
        return $config;
    }

    public function action_index()
    {
        $this->view('jqgrid')->config = $this->gridConfig();
        $view = $this->view($this->_gridViewName);
        $this->gridViewBefore(array('view'=>$view));
        $this->layout($this->_gridLayoutName);
    }

    public function gridViewBefore($args)
    {
        $args['view']->set(array(
            'title' => $this->_gridTitle,
            'actions' => array(
                'new' => ' <button class="st1 sz2 btn" onclick="location.href=\''.BApp::href($this->_formHref).'\'"><span>New '.BView::i()->q($this->_recordName).'</span></button>',
            ),
        ));
        BPubSub::i()->fire(static::$_origClass.'::gridViewBefore', $args);
    }

    public function action_grid_data()
    {
        $mc = $this->_modelClass;
        $orm = $mc::i()->orm($this->_mainTableAlias)->select($this->_mainTableAlias.'.*');
        $this->gridOrmConfig($orm);

        $export = BRequest::i()->request('export');

        $oc = static::$_origClass;

        $grid = FCom_Admin_View_Grid::i();
        if ($export) {
            $grid->set('config', $this->gridConfig())->export($orm, $oc);
        } else {
            $data = $grid->processORM($orm, $oc.'::action_grid_data', $oc);
            BResponse::i()->json($data);
        }
    }

    public function gridOrmConfig($orm)
    {
        BPubSub::i()->fire(static::$_origClass.'::gridOrmConfig', array('orm'=>&$orm));
    }

    public function action_grid_data__POST()
    {
        $this->_processGridDataPost($this->_modelClass);
    }

    public function action_form()
    {
        $class = $this->_modelClass;
        $id = BRequest::i()->params('id', true);
        if ($id && !($model = $class::i()->load($id))) {
            BDebug::error('Invalid ID: '.$id);
        }
        if (empty($model)) {
            $model = $class::i()->create();
        }
        $view = $this->view($this->_formViewName)->set('model', $model);
        $this->formViewBefore(array('view'=>$view, 'model'=>$model));
        $this->layout($this->_formLayoutName);
        $this->processFormTabs($view, $model, 'edit');
    }

    public function formViewBefore($args)
    {
        $m = $args['model'];
        $args['view']->set(array(
            'form_id' => BLocale::transliterate($this->_formLayoutName),
            'form_url' => BApp::href($this->_formHref).'?id='.$m->id,
            'actions' => array(
                'back' => '<button type="button" class="st3 sz2 btn" onclick="location.href=\''.BApp::href($this->_gridHref).'\'"><span>Back to list</span></button>',
                'delete' => '<button type="submit" class="st2 sz2 btn" name="do" value="DELETE" onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>Delete</span></button>',
                'save' => '<button type="submit" class="st1 sz2 btn" onclick="return adminForm.saveAll(this)"><span>Save</span></button>',
            ),
        ));
        BPubSub::i()->fire(static::$_origClass.'::formViewBefore', $args);
    }

    public function action_form__POST()
    {
        $r = BRequest::i();
        try {
            $class = $this->_modelClass;
            $id = $r->params('id', true);
            $model = $id ? $class::i()->load($id) : $class::i()->create();
            $data = $r->post('model');
            $args = array('id'=>$id, 'do'=>$r->post('do'), 'data'=>&$data, 'model'=>&$model);
            $this->formPostBefore($args);
            if ($r->post('do')==='DELETE') {
                $model->delete();
                BSession::i()->addMessage('The record has been deleted', 'success', 'admin');
            } else {
                $model->set($data)->save();
                BSession::i()->addMessage('Changes have been saved', 'success', 'admin');
            }
            $this->formPostAfter($args);
        } catch (Exception $e) {
            $this->formPostError($args);
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }

        if ($r->xhr()) {
            $this->forward('form', null, array('id'=>$id));
        } else {
            BResponse::i()->redirect(BApp::href($this->_gridHref));
        }
    }

    public function formPostBefore($args)
    {
        BPubSub::i()->fire(static::$_origClass.'::formPostBefore', $args);
    }

    public function formPostAfter($args)
    {
        BPubSub::i()->fire(static::$_origClass.'::formPostAfter', $args);
    }

    public function formPostError($args)
    {
        BPubSub::i()->fire(static::$_origClass.'::formPostError', $args);
    }
}