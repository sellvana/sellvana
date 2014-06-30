<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract class FCom_Admin_Controller_Abstract_GridForm extends FCom_Admin_Controller_Abstract
{
    // Required parameters
    protected $_modelClass;# = 'Model_Class_Name';
    protected $_gridHref;# = 'feature';

    // Optional parameters
    protected $_permission;# = 'feature/permission';
    protected $_navPath;# = 'nav/subnav';
    protected $_recordName = 'Record';
    protected $_gridTitle = 'List of Records';
    protected $_gridPageViewName = 'admin/grid';
    protected $_gridViewName = 'core/backbonegrid';
    protected $_gridLayoutName;# = '/feature';
    protected $_gridConfig = [];
    protected $_formHref;# = 'feature/form';
    protected $_formLayoutName;# = '/feature/form';
    protected $_formViewPrefix;# = 'module/feature-form/';
    protected $_formViewName = 'admin/form';
    protected $_formTitle;# = 'Record';
    protected $_mainTableAlias = 'main';

    protected $_useDefaultLayout = true;

    public function __construct()
    {
        parent::__construct();
        $this->_gridHref = trim($this->_gridHref, '/');

        if (is_null($this->_permission))     $this->_permission = $this->_gridHref;
        if (is_null($this->_navPath))        $this->_navPath = $this->_permission;

        if (is_null($this->_gridLayoutName)) $this->_gridLayoutName = '/' . $this->_gridHref;
        if (is_null($this->_gridViewName))   $this->_gridViewName = 'core/backbonegrid';

        if (is_null($this->_formHref))       $this->_formHref = $this->_gridHref . '/form';
        if (is_null($this->_formLayoutName)) $this->_formLayoutName = $this->_gridLayoutName . '/form';
        if (is_null($this->_formViewName))   $this->_formViewName = 'admin/form';
        if (is_null($this->_formViewPrefix)) $this->_formViewPrefix = $this->_gridHref . '-form/';

        if (is_null($this->_mainTableAlias)) $this->_mainTableAlias = 'main';

    }

    public function gridView()
    {
        $view = $this->view($this->_gridViewName);
        $config = $this->_processConfig($this->gridConfig());
        $this->gridOrmConfig($config['orm']);
        $view->set('grid', ['config' => $config]);
        $this->BEvents->fire(static::$_origClass . '::gridView', ['view' => $view]);
        return $view;
    }

    public function gridConfig()
    {
        $gridDataUrl = $this->BApp->href($this->_gridHref . '/grid_data');
        #$gridHtmlUrl = $this->BApp->href($this->_gridHref.'/grid_html');
        $gridHtmlUrl = $this->BApp->href($this->_gridHref);
        $formUrl = $this->BApp->href($this->_formHref);
        $modelClass = $this->_modelClass;
        $config = [
            'id' => static::$_origClass,
            'orm' => $modelClass ? $this->{$modelClass}->orm($this->_mainTableAlias)->select($this->_mainTableAlias . '.*') : null,
            #'orm' => $modelClass,
            'data_url' => $gridDataUrl,
            'edit_url' => $gridDataUrl,
            'grid_url' => $gridHtmlUrl,
            'form_url' => $formUrl,
            'columns' => [
            ],
        ];
        $config = array_merge($config, $this->_gridConfig);
        return $config;
    }

    public function simpleGridConfig()
    {
        $config = [
            'columns' => [],
            'data' => [],
        ];

        return $config;

    }

    protected function _processConfig($config)
    {
        return $config;
    }

    public function action_index()
    {
        $this->layout();
        if ($this->BRequest->xhr()) {
            $this->BResponse->set($this->gridView())->output();
        }

        if (($head = $this->view('head'))) {
            $head->addTitle($this->_gridTitle);
        }

        if (($nav = $this->view('admin/nav'))) {
            $nav->setNav($this->_navPath);
        }

        $pageView = $this->view($this->_gridPageViewName);
        $view = $this->gridView();
        $this->gridViewBefore(['view' => $view, 'page_view' => $pageView]);

        if ($this->_useDefaultLayout) {
            $this->BLayout->applyLayout('default_grid');
        }
        $this->BLayout->applyLayout($this->_gridLayoutName);
    }

    public function gridViewBefore($args)
    {
        $view = $args['page_view'];
        $hlp = $this->BView;
        $view->set([
            'title' => $this->_gridTitle,
            'actions' => [
                'new' => ' <button type="button" class="btn btn-primary btn-sm" onclick="location.href=\''
                    . $this->BApp->href($this->_formHref) . '\'"><span>'
                    . $hlp->q($hlp->_('New %s', $this->_recordName))
                    . '</span></button>',
            ],
        ]);
        $this->BEvents->fire(static::$_origClass . '::gridViewBefore', $args);
    }

    public function action_grid_html()
    {
        $this->BResponse->set($this->gridView())->output();
    }

    public function action_grid_data()
    {
        if ($this->BRequest->get('export')) {
            if ($this->BRequest->csrf('referrer', 'GET')) {
                $this->BResponse->status('403', 'Invalid referrer', 'Invalid referrer');
                return;
            }
        } else {
            if (!$this->BRequest->xhr()) {
                $this->BResponse->status('403', 'Available only for XHR', 'Available only for XHR');
                return;
            }
        }

        $view = $this->gridView();
        $grid = $view->get('grid');

        if (isset($grid['config']['data']) && (!empty($grid['config']['data']))) {
            $data = $grid['config']['data'];
            $data = $this->gridDataAfter($data);
            $this->BResponse->json([['c' => 1], $data]);
        } else {
            $r = $this->BRequest->get();
            //TODO: clean up and remove
            if (empty($grid['config']['orm'])) {
                $mc = $this->_modelClass;
                $grid['config']['orm'] = $this->{$mc}->orm($this->_mainTableAlias)
                    ->select($this->_mainTableAlias . '.*');
                $view->set('grid', $grid);
            }
            if (isset($r['filters'])) {
                $filters = $this->BUtil->fromJson($r['filters']);
                if (isset($filters['exclude_id']) && $filters['exclude_id'] != '') {
                    $arr = explode(',', $filters['exclude_id']);
                    $grid['config']['orm']->where_not_in($this->_mainTableAlias . '.id', $arr);
                }
            }
            #$this->gridOrmConfig( $grid[ 'config' ][ 'orm' ] );

            $oc = static::$_origClass;

            $gridId = !empty($grid['config']['id']) ? $grid['config']['id'] : $oc;

            if ($this->BRequest->request('export')) {
                $data = $view->generateOutputData(true);
                $view->export($data['rows'], $oc);
            } else {

                //$data = $view->processORM($orm, $oc.'::action_grid_data', $gridId);
                $data = $view->generateOutputData();
                $data = $this->gridDataAfter($data);
                $this->BResponse->json([
                    ['c' => $data['state']['c']],
                    $this->BDb->many_as_array($data['rows']),
                ]);
            }
        }
    }

    public function gridDataAfter($data)
    {
        $this->BEvents->fire(static::$_origClass . '::gridDataAfter', ['data' => &$data]);
        return $data;
    }

    public function gridOrmConfig($orm)
    {
        $this->BEvents->fire(static::$_origClass . '::gridOrmConfig', ['orm' => &$orm]);
    }

    public function action_grid_data__POST()
    {
        $this->_processGridDataPost($this->_modelClass);
    }

    public function action_form()
    {
        $class = $this->_modelClass;
        $id = $this->BRequest->param('id', true);
        if ($id && !($model = $this->{$class}->load($id))) {
            /*$this->BDebug->error('Invalid ID: '.$id);*/
            $this->message('This item does not exist', 'error');
        }
        if (empty($model)) {
            $model = $this->{$class}->create();
        }
        $this->layout();
        $this->formMessages();
        $view = $this->view($this->_formViewName)->set('model', $model);
        $this->formViewBefore(['view' => $view, 'model' => $model]);

        if ($this->_formTitle && ($head = $this->view('head'))) {
            $head->addTitle($this->_formTitle);
        }

        if (($nav = $this->view('admin/nav'))) {
            $nav->setNav($this->_navPath);
        }

        $this->BLayout->view('admin/form')->set('tab_view_prefix', $this->_formViewPrefix);
        if ($this->_useDefaultLayout) {
            $this->BLayout->applyLayout('default_form');
        }
        $this->BLayout->applyLayout($this->_formLayoutName);

        $this->processFormTabs($view, $model);
    }

    public function formViewBefore($args)
    {
        $m = $args['model'];
        $actions = [];

        $actions['back'] = '<button type="button" class="btn btn-link" onclick="location.href=\''
            . $this->BApp->href($this->_gridHref) . '\'"><span>' .  $this->BLocale->_('Back to list') . '</span></button>';
        if ($m->id) {
            $actions['delete'] = '<button type="submit" class="btn btn-warning ignore-validate" name="do" value="DELETE" '
                . 'onclick="return confirm(\'Are you sure?\')"><span>' .  $this->BLocale->_('Delete') . '</span></button>';
        }
        $actions['save'] = '<button type="submit" class="btn btn-primary" onclick="return adminForm.saveAll(this)"><span>'
            . $this->BLocale->_('Save') . '</span></button>';

        $id = method_exists($m, 'id') ? $m->id() : $m->id;
        $title = $id ? $this->BLocale->_('Edit %s: %s', [$this->_recordName, $m->title]) : $this->BLocale->_('Create New %s', [$this->_recordName]);

        $args['view']->set([
            'form_id' => $this->formId(),
            'form_url' => $this->BApp->href($this->_formHref) . '?id=' . $m->id,
            'title' => $title,
            'actions' => $actions,
        ]);
        $this->BEvents->fire(static::$_origClass . '::formViewBefore', $args);
    }

    public function action_form__POST()
    {
        $r = $this->BRequest;
        $args = [];
        $formId = $this->formId();
        $redirectUrl = $this->BApp->href($this->_gridHref);
        try {
            $class = $this->_modelClass;
            $id = $r->param('id', true);
            $model = $id ? $this->{$class}->load($id) : $this->{$class}->create();
            if (!$model) {
                throw new BException("This item does not exist");
            }
            $data = $r->post('model');
            $args = ['id' => $id, 'do' => $r->post('do'), 'data' => &$data, 'model' => &$model];
            $this->formPostBefore($args);
            $args['validateFailed'] = false;
            if ($r->post('do') === 'DELETE') {
                $model->delete();
                $this->message('The record has been deleted');
            } else {
                $model->set($data);

                if ($model->validate($model->as_array(), [], $formId)) {
                    $model->save();
                    $this->message('Changes have been saved');
                    if ($r->post('do') === 'saveAndContinue') {
                        $redirectUrl = $this->BApp->href($this->_formHref) . '?id=' . $model->id();
                    }
                } else {
                    $this->message('Cannot save data, please fix above errors', 'error', 'validator-errors:' . $formId);
                    $args['validateFailed'] = true;
                    $redirectUrl = $this->BApp->href($this->_formHref) . '?id=' . $id;
                }

            }
            $this->formPostAfter($args);
        } catch (Exception $e) {
            //$this->BDebug->exceptionHandler($e);
            $this->formPostError($args);
            $this->message($e->getMessage(), 'error');
            $redirectUrl = $this->BApp->href($this->_formHref) . '?id=' . $id;
        }
        if ($r->xhr()) {
            $this->forward('form', null, ['id' => $id]);
        } else {
            $this->BResponse->redirect($redirectUrl);
        }
    }

    public function formPostBefore($args)
    {
        $this->BEvents->fire(static::$_origClass . '::formPostBefore', $args);
    }

    public function formPostAfter($args)
    {
        $this->BEvents->fire(static::$_origClass . '::formPostAfter', $args);
    }

    public function formPostError($args)
    {
        $this->BEvents->fire(static::$_origClass . '::formPostError', $args);
    }

    /**
     * use form id for html and namespace in messages
     * @return string
     */
    public function formId()
    {
        return $this->BLocale->transliterate($this->_formLayoutName);
    }

    /**
     * Prepare message for form
     *
     * This is a temporary solution to save dev time
     *
     * @todo implement form errors inside form as error labels instead of group on top
     * @param string $viewName
     */
    public function formMessages($viewName = 'core/messages')
    {
        $formId = $this->formId();
        $messages = $this->BSession->messages('validator-errors:' . $formId);
        if (count($messages)) {
            $msg = [];
#$this->BDebug->dump($messages); exit;
            foreach ($messages as $m) {
                $msg[] = is_array($m['msg']) ? $m['msg']['error'] : $m['msg'];
            }
            $this->message($msg, 'error');
        }
    }
}
