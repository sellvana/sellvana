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
    #protected $_gridViewName = 'admin/grid';
    protected $_gridViewName = 'core/backgrid';
    protected $_gridLayoutName;# = '/feature';
    protected $_formHref;# = 'feature/form';
    protected $_formLayoutName;# = '/feature/form';
    protected $_formViewName = 'admin/form';
    protected $_formTitle;# = 'Record';
    protected $_mainTableAlias = 'main';

    public function __construct()
    {
        parent::__construct();
        $this->_gridHref = trim($this->_gridHref, '/');
        if (is_null($this->_permission))     $this->_permission = $this->_gridHref;
        if (is_null($this->_gridLayoutName)) $this->_gridLayoutName = '/'.$this->_gridHref;
        if (is_null($this->_formHref))       $this->_formHref = $this->_gridHref.'/form';
        if (is_null($this->_formLayoutName)) $this->_formLayoutName = $this->_gridLayoutName.'/form';
        if (is_null($this->_gridViewName))   $this->_formViewName = 'core/backgrid';
        if (is_null($this->_formViewName))   $this->_formViewName = 'admin/form';
        if (is_null($this->_mainTableAlias)) $this->_mainTableAlias = 'main';
    }

    public function gridView()
    {
        $view = $this->view($this->_gridViewName);
        if (method_exists($this, 'gridConfig')) {
            $config = $this->gridConfig();
            $view->set('grid', array('config' => $config['grid']));
        }
        BEvents::i()->fire(static::$_origClass.'::gridView', array('view'=>&$config));
        return $view;
    }


    public function gridConfig()
    {
        $gridDataUrl = BApp::href($this->_gridHref.'/grid_data');
        $formUrl = BApp::href($this->_formHref);
        $config = array(
            'grid'=>array(
                'id' => static::$_origClass,
                'data_url' => $gridDataUrl,
                'edit_url' => $gridDataUrl,
                'columns' => array(
                ),
            ),
        );
        return $config;
    }

    public function action_index()
    {
        //$this->view('jqgrid')->set('config', $this->gridConfig());
        if (($head = $this->view('head'))) {
            $head->addTitle($this->_gridTitle);
        }
        $view = $this->gridView();
        $this->gridViewBefore(array('view' => $view));
        $this->layout($this->_gridLayoutName);
    }

    public function gridViewBefore($args)
    {
        $this->view('admin/grid')->set(array(
            'title' => $this->_gridTitle,
            'actions' => array(
                'new' => ' <button class="st1 sz2 btn" onclick="location.href=\''.BApp::href($this->_formHref).'\'"><span>New '.BView::i()->q($this->_recordName).'</span></button>',
            ),
        ));
        BEvents::i()->fire(static::$_origClass.'::gridViewBefore', $args);
    }

    public function action_grid_data()
    {
        $view = $this->gridView();
        $grid = $view->get('grid');
        if (empty($grid['orm'])) {
            $mc = $this->_modelClass;
            $grid['orm'] = $mc::i()->orm($this->_mainTableAlias)->select($this->_mainTableAlias.'.*');
            $view->set('grid', $grid);
        }
        $this->gridOrmConfig($grid['orm']);

        $oc = static::$_origClass;

        $config = $view->grid['config'];
        $gridId = !empty($config['id']) ? $config['id'] : $oc;

        if (BRequest::i()->request('export')) {
            $view->export($grid['orm'], $oc);
        } else {
            //$data = $view->processORM($orm, $oc.'::action_grid_data', $gridId);
            $data = $view->outputData();
            //$data = $this->gridDataAfter($data);
            BResponse::i()->json(array(
                array('c' => $data['state']['c']),
                BDb::many_as_array($data['rows']),
            ));
        }
    }

    public function gridDataAfter($data)
    {
        return $data;
    }

    public function gridOrmConfig($orm)
    {
        BEvents::i()->fire(static::$_origClass.'::gridOrmConfig', array('orm'=>&$orm));
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
        if ($this->_formTitle && ($head = $this->view('head'))) {
            $head->addTitle($this->_formTitle);
        }
    }

    public function formViewBefore($args)
    {
        $m = $args['model'];
        $actions = array();

        $actions['back'] = '<button type="button" class="btn btn-link" onclick="location.href=\''.BApp::href($this->_gridHref).'\'"><span>Back to list</span></button>';
        if ($m->id) {
            $actions['delete'] = '<button type="submit" class="btn btn-warning" name="do" value="DELETE" onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>Delete</span></button>';
        }
        $actions['save'] = '<button type="submit" class="btn btn-primary" onclick="return adminForm.saveAll(this)"><span>Save</span></button>';

        $args['view']->set(array(
            'form_id' => BLocale::transliterate($this->_formLayoutName),
            'form_url' => BApp::href($this->_formHref).'?id='.$m->id,
            'actions' => $actions,
        ));
        BEvents::i()->fire(static::$_origClass.'::formViewBefore', $args);
    }

    public function action_form__POST()
    {
        $r = BRequest::i();
        $args = array();
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
        BEvents::i()->fire(static::$_origClass.'::formPostBefore', $args);
    }

    public function formPostAfter($args)
    {
        BEvents::i()->fire(static::$_origClass.'::formPostAfter', $args);
    }

    public function formPostError($args)
    {
        BEvents::i()->fire(static::$_origClass.'::formPostError', $args);
    }
}
