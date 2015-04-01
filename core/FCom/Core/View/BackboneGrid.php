<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Core_View_BackboneGrid
 *
 * @property array $grid
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */
class FCom_Core_View_BackboneGrid extends FCom_Core_View_Abstract
{
    static protected $_defaultActions = [
        'refresh' => true,
        'link_to_page' => true,
        'columns' => true,
        'delete' => true,
        'edit' => true,
        'add' => true,
        'new' => true,
        'export' => true
    ];

    /**
     * @param array $changeRequest
     * @return string
     */
    public function gridUrl($changeRequest = [])
    {
        if (!$changeRequest) {
            return $this->grid['config']['grid_url'];
        }
        return $this->BUtil->setUrlQuery($this->grid['config']['grid_url'], $changeRequest);
    }

    /**
     * @return string
     */
    public function pageSizeHref()
    {
        return $this->BUtil->setUrlQuery(true, ['ps' => '-VALUE-']);
    }

    /**
     * @return array
     */
    public function pageSizeOptions()
    {
        $pageSizes = $this->grid['config']['page_size_options'];
        return array_combine($pageSizes, $pageSizes);
    }

    /**
     * @return string
     */
    public function pageChangeHref()
    {
        return $this->BUtil->setUrlQuery(true, ['p' => '-VALUE-']);
    }

    /**
     * @return array
     */
    public function gridActions()
    {
        if (empty($this->grid['config']['actions'])) {
            return [];
        }
        return $this->grid['config']['actions'];
    }

    /**
     * @param $cb
     * @param $args
     * @return mixed
     */
    public function callUserFunc($cb, $args)
    {
        return $this->BUtil->call($cb, $args, true);
    }

    /**
     * @return array
     */
    public function multiselectToggleOptions()
    {
        return [
            'show_all' => 'Show All',
            'show_sel' => 'Show Selected',
            'upd_sel' => 'Select Visible',
            'upd_unsel' => 'Unselect Visible',
            'upd_clear' => 'Unselect All',
            /*'@Show'=>array(
                'show_all'=>'All',
                'show_sel'=>'Sel'
            ),
            '@Select'=>array(
                'upd_sel'=>'Sel',
                'upd_unsel'=>'Unsel',
                'upd_clear'=>'Clear'
            ),*/
        ];
    }

    /**
     * @return string
     */
    public function multiselectCurrent()
    {
        $grid = $this->get('grid');
        return !empty($grid['request']['selected']) ? $grid['request']['selected'] : '';
    }

    /**
     * @param $col
     * @return string
     */
    public function sortHref($col)
    {
        $grid = $this->get('grid');
        if (empty($col['name']) || isset($col['sortable']) && !$col['sortable']) {
            return '#';
        }
        if (!empty($grid['request']['s']) && $grid['request']['s'] == $col['name']) {
            $change = ['sd' => $grid['request']['sd'] == 'desc' ? 'asc' : 'desc'];
        } else {
            $change = ['s' => $col['name'], 'sd' => 'asc'];
        }
        return $this->BUtil->setUrlQuery(true, $change);
    }

    /**
     * @param $col
     * @return string
     */
    public function sortStyle($col)
    {
        return !empty($col['width']) ? "width:{$col['width']}px" : '';
    }

    /**
     * @param $col
     * @return string
     */
    public function sortClass($col)
    {
        $classArr = [];
        if (empty($col['no_reorder'])) $classArr[] = 'js-draggable';

        $s = $this->grid['result']['state'];
        if (!empty($s['s']) && !empty($col['name']) && $s['s'] == $col['name']) {
            $classArr[] = 'sort-' . $s['sd'];
        } else {
            $classArr[] = 'sort';
        }

        return join(' ', $classArr);
    }

    /**
     * @param $col
     * @return string
     */
    public function colFilterHtml($col)
    {
        return '';
    }

    /**
     * process default value for grid config
     */
    protected function _processDefaults()
    {
        //TODO: remember processed config
        $grid = $this->grid;
        $c =& $grid['config'];


        if (!empty($c['data_mode']) && $c['data_mode'] === 'local') {
            unset($c['data_url']);

            //IMPORTANT: edit_url_required is used when local mode grid needs to be saved through edit_url
            //ex) ProductReviewGrid on product edit form
            if (empty($c['edit_url_required']) || !$c['edit_url_required']) {
                unset($c['edit_url']);
            }

        }

        if (empty($c['grid_url'])) {
            $c['grid_url'] = $this->BRequest->currentUrl();
        }
        if (empty($c['page_size_options'])) {
            $c['page_size_options'] = [10, 25, 50, 100];
        }
        if (empty($c['state']['ps'])) {
            $c['state']['ps'] = $c['page_size_options'][0];
        }
        if (!isset($c['state']['s'])) {
            $c['state']['s'] = '';
        }
        if (!isset($c['state']['sd'])) {
            $c['state']['sd'] = 'asc';
        }
        if (empty($c['search'])) {
            $c['search'] = new stdClass;
        }
        if (empty($c['row_id_column'])) {
            $c['row_id_column'] = 'id';
        }
        unset($c);

        // fetch request parameters
        if (empty($grid['request'])) {
            $grid['request'] = $this->BRequest->get();
        }


        $this->grid = $grid;
    }

    /**
     * process column from grid config
     */
    protected function _processColumnsConfig()
    {
        $grid = $this->grid;
        $pos = 0;

        foreach ($grid['config']['columns'] as $cId => &$col) {
            if (empty($col['name'])) {
                $col['name'] = $cId;
            }

            if ($cId === 0) {
                $col['cssClass'] = 'select-row';
                $col['edit'] = 'inline';
            }

            if (empty($col['type'])) {
                if (!empty($col['editor'])) {
                    $col['type'] = 'input';
                }

                continue;
            }

            switch ($col['type']) {
                case 'multiselect':
                    $col['width'] = 50;
                    $col['no_reorder'] = true;

                    break;
                case 'input':
                    /*if (!empty($col['editor']) && $col['editor'] === 'select' && !empty($col['options'])) {

                        $temp = array();
                        foreach($col['options'] as $key=>$val) {

                            if (is_array($val)) {
                                $temp[] = $val;
                            } else {
                                $temp[] = array('label'=>$val, 'value'=>$key);
                            }
                        }
                        $col['options'] = $temp;

                    }*/


                    break;
                case 'btn_group':
                    $col['label'] = 'Actions';
                    $col['name'] = 'btn_group';
                    $col['sortable'] = false;
                    foreach ($col['buttons'] as $bId => &$btn) {
                        if (empty($btn['col'])) {
                            $btn['col'] = 'id';
                        }

                        switch ($btn['name']) {
                            case 'edit':
                                if (empty($btn['icon'])) {
                                    $btn['icon'] = ' icon-pencil ';
                                }
                                if (!empty($grid['config']['form_url']) && empty($btn['href'])) {
                                    $btn['href'] = $grid['config']['form_url'] . '?' . $btn['col'] . '=';
                                }
                                $btn['cssClass'] = (isset($btn['cssClass'])) ? $btn['cssClass']: ' btn-xs btn-edit ';
//                                $btn['cssClass'] = ' btn-xs btn-edit ';
                                break;

                            case 'delete':
                                $btn['icon'] = 'icon-trash';
                                $btn['cssClass'] = ' btn-delete ';
                                if (!empty($btn['noconfirm']) && $btn['noconfirm']) {
                                    $btn['cssClass'] .= ' noconfirm ';
                                }
                                break;
                        }

                        if (!empty($btn['href'])) {
                            $btn['type'] = 'link';
                            if (!$this->BUtil->isUrlFull($btn['href'])) {
                                $btn['href'] = $this->BApp->href($btn['href']);
                            }
                        }

                        //TODO: Is it really necessary not to have default icon when button has caption?
                        if (!empty($btn['caption'])) {
                            $btn['icon'] = '';
                        }
                    }


                    break;

            }
            /*$col['position'] = ++$pos;
            switch ($cId) {
                case '_multiselect':
                    $col['type'] = 'multiselect';
                    $col['width'] = 50;
                    $col['no_reorder'] = true;
                    $col['format'] = function($args) {
                        return $this->BUtil->tagHtml('input', array(
                            'type' =>'checkbox',
                            'name' =>"grid[{$args['grid']['config']['id']}][sel][{$args['row']->id}]",
                            'class'=>'js-sel',
                        ));
                    };
                    break;

                case '_actions':
                    $col['type'] = 'actions';
                    $col['label'] = 'Actions';
                    //$col['width'] = 50;
                    $col['no_reorder'] = true;
                    $col['format'] = function($args) use($col) {
                        $options = array(''=>'');
                        if (!empty($col['options'])) {
                            foreach ($col['options'] as $k=>$opt) {
                                if (!empty($opt['data-href'])) {
                                    $opt['data-href'] = $this->BUtil->injectVars($opt['data-href'], $args['row']->as_array());
                                }
                                $options[$k] = $opt;
                            }
                        }
                        return $this->BUtil->tagHtml('select', array('class'=>'js-actions'), $this->BUtil->optionsHtml($options));
                    };
                    break;
            }*/
        }
        unset($col);
        $this->grid = $grid;
    }

    /**
     * process filter from grid config
     */
    protected function _processFiltersConfig()
    {
        if (empty($this->grid['config']['filters'])) {
            return;
        }
        $grid = $this->grid;

        foreach ($grid['config']['filters'] as $k => &$filter) {
            if (empty($filter['type'])) {
                $filter['type'] = 'text';
            }
        }
        unset($filter);
        $this->grid = $grid;
    }

    /**
     * process action button from grid config
     */
    protected function _processActionsConfig()
    {
        if (empty($this->grid['config']['actions'])) {
            return;
        }
        $grid = $this->grid;

        foreach ($grid['config']['actions'] as $k => &$action) {
            //var_dump($action);

            $html = $caption = $class = '';

            if (!empty(static::$_defaultActions[$k])) {

                switch ($k) {
                    case 'refresh':
                        $caption = isset($action['caption']) ? $action['caption'] : $this->BLocale->_('Refresh');
                        $class   = 'js-change-url grid-refresh btn';
                        $html    = $this->BUtil->tagHtml('a', ['href' => '#', 'class' => $class], $caption);
                        break;
                    case 'export':
                        $caption = isset($action['caption']) ? $action['caption'] : $this->BLocale->_('Export');
                        $class   = 'grid-export btn';
                        $html    = $this->BUtil->tagHtml('button', ['type' => 'button', 'class' => $class], $caption);
                        break;
                    case 'link_to_page':
                        $caption = isset($action['caption']) ? $action['caption'] : $this->BLocale->_('Export');
                        $class   = 'grid-export btn';
                        $html    = $this->BUtil->tagHtml('a', ['href' => $action['href'], 'class' => $class], $caption);
                        break;
                    case 'edit':
                        $caption = isset($action['caption']) ? $action['caption'] : $this->BLocale->_('Edit');
                        $class   = 'btn grid-mass-edit mass-action btn-success';
                        $html    = $this->BUtil->tagHtml('a',
                            ['class' => $class .' disabled', 'data-toggle' => 'modal', 'href' => '#' . $grid['config']['id'] . '-mass-edit', 'role' => 'button'],
                            $caption
                        );
                        break;
                    case 'delete':
                        $caption = isset($action['caption']) ? $action['caption'] : $this->BLocale->_('Delete');
                        $class   = 'btn grid-mass-delete mass-action btn-danger' . ((isset($action['confirm']) && $action['confirm'] === false) ? ' noconfirm' : '');
                        $html    = $this->BUtil->tagHtml('button', ['class' => $class . ' disabled', 'type' => 'button'], $caption);
                        break;
                    case 'add': //todo: confirm with Boris merge this action with 'new'
                        $caption = isset($action['caption']) ? $action['caption'] : $this->BLocale->_('Add');
                        $class   = 'btn grid-add btn-primary';
                        $html    = $this->BUtil->tagHtml('button', ['class' => $class, 'type' => 'button'], $caption);
                        break;
                    case 'new':
                        $caption = isset($action['caption']) ? $action['caption'] : $this->BLocale->_('Add');
                        $class   = 'btn grid-new btn-primary' . (isset($action['modal']) && $action['modal'] ? ' _modal' : '');
                        $html    = $this->BUtil->tagHtml('button', ['class' => $class, 'type' => 'button'], $caption);
                        break;
                    default:
                        $action = static::$_defaultActions[$k];
                }
            } elseif (!isset($action['html']) || !$action['html']) {
                $caption = isset($action['caption']) ? $action['caption'] : $this->BLocale->_('Add');
                $class = isset($action['class']) ? 'btn ' . $action['class'] : 'btn';
                $html = $this->BUtil->tagHtml('button', ['class' => $class, 'type' => 'button', 'id' => isset($action['id']) ? $action['id'] : ''], $caption);
            }

            if ($html && $class && $caption) {
                $data = [
                    'html'    => $html,
                    'caption' => isset($action['caption']) ? $action['caption'] : $caption,
                    'class'   => $class,
                ];

                $action = is_array($action) ? array_merge($action, $data) : $data;
            }
        }
        unset($action, $data);
        $this->grid = $grid;
    }

    /**
     * @param $state
     * @return mixed
     */
    protected function _personalizePageState($state)
    {
        return $state;
    }

    /**
     * process personalization of current user
     */
    protected function _processPersonalization()
    {
        $grid = $this->grid;
        $gridId = !empty($grid['personalize']['id']) ? $grid['personalize']['id'] : $grid['config']['id'];

        // retrieve current personalization
        $pers = $this->FCom_Admin_Model_User->personalize();
        $persGrid = !empty($pers['grid'][$gridId]) ? $pers['grid'][$gridId] : [];
#var_dump($pers);
        $req = $this->BRequest->get();

        // prepare array to update personalization

        $personalize = [];
        foreach (['p', 'ps', 's', 'sd', 'q'] as $k) {
            if (!isset($persGrid['state'][$k])) {
                $persGrid['state'][$k] = null;
            }
            if (isset($req[$k]) && $persGrid['state'][$k] !== $req[$k]) {
                $personalize['state'][$k] = $req[$k];
            } elseif (isset($persGrid[$k])) {
                $grid['config']['state'][$k] = $persGrid[$k];
            }
        }
        // save personalization
        if (!empty($personalize)) {
            $this->FCom_Admin_Model_User->personalize(['grid' => [$gridId => $personalize]]);
        }

        // get columns personalization
        $persCols = [];
        $defPos = 0;
        foreach ($grid['config']['columns'] as $col) {
            if (!empty($col['name']) && !empty($persGrid['columns'][$col['name']])) {
                $col = $this->BUtil->arrayMerge($col, $persGrid['columns'][$col['name']]);
            }
            if (empty($col['position'])) {
                $col['position'] = $defPos;
            }
            $defPos++;
            $persCols[] = $col;
        }
        usort($persCols, function($a, $b) { return $a['position'] - $b['position']; });
        $grid['config']['columns'] = $persCols;

        //get filters personalization
        $persFilters = [];
        $defPos = 0;
        if (!isset($grid['config']['filters']) && !empty($persGrid['filters'])) {
            $grid['config']['filters'] = [];
        }

        if (isset($grid['config']['filters'])) {
            foreach ($grid['config']['filters'] as $filter) {
                if (!empty($filter['field']) && !empty($persGrid['filters'][$filter['field']])) {
                    $filter = $this->BUtil->arrayMerge($filter, $persGrid['filters'][$filter['field']]);
                }
                if (!isset($filter['position'])) {
                    $filter['position'] = $defPos;
                }
                $defPos++;
                $persFilters[] = $filter;
            }

            usort($persFilters, function($a, $b) { return $a['position'] - $b['position']; });
            $grid['config']['filters'] = $persFilters;
        }
#$this->BDebug->dump($persGrid); $this->BDebug->dump($grid); exit;
        $this->grid = $grid;
    }

    /**
     * reset personalization config
     */
    protected function _resetPersonalization()
    {
        $grid = $this->grid;
        $gridId = !empty($grid['personalize']['id']) ? $grid['personalize']['id'] : $grid['config']['id'];
        $reset = ['state' => ['p' => null, 'ps' => null, 's' => null, 'sd' => null, 'q' => null, 'filters' => null], 'filters' => null];
        $this->FCom_Admin_Model_User->personalize(['grid' => [$gridId => $reset]]);
    }

    /**
     * @return array
     */
    public function getGrid()
    {
        if (!empty($this->grid['_processed'])) {
            return $this->grid;
        }

        $grid = $this->grid;
        $this->BEvents->fire(__METHOD__ . ':before', ['grid' => &$grid]);
        $this->grid = $grid;

        $this->_processDefaults();
        $this->_processColumnsConfig();
        $this->_processFiltersConfig();
        $this->_processActionsConfig();
        $this->_processPersonalization();

        $grid = $this->grid;
        $this->BEvents->fire(__METHOD__ . ':after', ['grid' => &$grid]);
        $grid['_processed'] = true;
        $this->grid = $grid;

        return $grid;
    }

    /**
     * @return mixed
     * @throws BException
     * @throws Exception
     */
    public function getGridConfig()
    {
        //TODO: replace magic with science
        $data = $this->getGridConfigData(); // initialize config and data
        $grid = $this->getGrid();
        $config = $grid['config'];
        $config['data'] = $this->getPageRowsData();
        $config['personalize_url'] = $this->BApp->href('my_account/personalize');

        return $config;
    }

    /**
     * @param array $options
     * @return array
     * @throws BException
     * @throws Exception
     */
    public function getGridConfigData(array $options = [])
    {
        //uncomment this code if we meet issue with stored value personalization, todo: need add this feature to Settings
        //$this->_resetPersonalization();
        // fetch grid configuration
        $grid = $this->getGrid();
        $config = $grid['config'];
        if (empty($config['orm']) && !isset($config['data'])) {
            throw new BException('Either ORM or data is required');
        }
        if (isset($config['data']) && !empty($config['data'])) {
            $gridId = $config['id'];
            $pers = $this->FCom_Admin_Model_User->personalize();
            $persState = !empty($pers['grid'][$gridId]['state']) ? $pers['grid'][$gridId]['state'] : [];

            //param 'q' is needed?
            $params = ["p", "ps", "s", "sd"/*,"q"*/];

            foreach ($params as $p) {
                $persState[$p] = isset($persState[$p]) ? $persState[$p]
                    : ((isset($config['state']) && isset($config['state'][$p])) ? $config['state'][$p] : null);
            }

            $persState['p'] = isset($persState['p']) ? $persState['p'] : 1;
            $persState['ps'] = isset($persState['ps']) ? $persState['ps'] : 10;
            $grid['result']['state'] = $persState;

            $grid['result']['rows'] = $config['data'];
        } elseif (!empty($config['orm'])) {
            $orm = $config['orm'];
            if (is_string($orm)) {
                $orm = $orm::i()->orm();
            }
            $this->BEvents->fire(__METHOD__ . ':initORM:' . $config['id'], ['orm' => $orm, 'grid' => $grid]);


            $gridId = $config['id'];
            $pers = $this->FCom_Admin_Model_User->personalize();
            $persState = !empty($pers['grid'][$gridId]['state']) ? $pers['grid'][$gridId]['state'] : [];
            $persFilters = !empty($persState['filters']) ? $persState['filters'] : [];
            $persState = $this->BUtil->arrayMask($persState, 's,sd,p,ps,q');

            $this->_processGridFilters($config, $persFilters, $orm);

            $config['state'] = $persState;
            $grid['request'] = (empty($grid['request']))? $persState: $grid['request'];
            try {
                $grid['result'] = $orm->paginate($grid['request'], [
                    's' => !empty($config['state']['s'])  ? $config['state']['s']  : null,
                    'sd' => !empty($config['state']['sd']) ? $config['state']['sd'] : null,
                    'p' => !empty($config['state']['p'])  ? $config['state']['p']  : null,
                    'ps' => !empty($config['state']['ps']) ? $config['state']['ps'] : $config['page_size_options'][0],
                ]);
            } catch (Exception $e) {
                $this->_resetPersonalization();
                throw $e;
            }

            //var_dump($grid['result']);exit;
            $grid['result']['state']['description'] = $this->stateDescription($grid['result']['state']);

            $this->BEvents->fire(__METHOD__ . ':after:' . $config['id'], ['grid' => & $grid]);
        }

        //$mapColumns = array();
        //$this->_processGridJoins($config, $mapColumns, $orm, 'before_count');

        foreach ($grid['config']['columns'] as &$column) {
            unset($column['index']);
        }
        unset($column);

        $this->grid = $grid;
        return $grid;
    }

    /**
     * @return array
     */
    public function getPageRowsData()
    {

        $grid = $this->get('grid');
        $state = isset($grid['result']['state']) ? $grid['result']['state'] : [];
        $rows = isset($grid['result']['rows']) ? $grid['result']['rows'] : [];
        //var_dump($state);
        $gridId = $grid['config']['id'];
//        $persState = !empty($grid['config']['state']) ? $grid['config']['state'] : array(); // overridden right after this section
        $pers = $this->FCom_Admin_Model_User->personalize();
        $persState = !empty($pers['grid'][$gridId]['state']) ? $pers['grid'][$gridId]['state'] : [];
        $persState = $this->BUtil->arrayMask($persState, 's,sd,p,ps,q');

        foreach ($persState as $k => $v) {
            if (!empty($v)) {
                $state[$k] = $v;
            }
        }
        //var_dump($state);

        $options = [];
        foreach ($grid['config']['columns'] as $col) {
            if (!empty($col['name']) && !empty($col['options'])) {
                $options[$col['name']] = $col['options'];
            }
        }

        $data = [];

        foreach ($rows as $rowId => $row) {
            $r = is_array($row) ? $row : $row->as_array();
            /*
            foreach ($r as $k => $v) {
                if (!empty($options[$k][$v])) {
                    $r[$k] = $options[$k][$v];
                }
            }
            */
            $data[] = $r;
        }

        if (!empty($grid['config']['page_rows_data_callback'])) {
            $callback = $this->BUtil->extCallback($grid['config']['page_rows_data_callback']);
            $data = call_user_func($callback, $data);
        }

        return ['state' => $state, 'data' => $data];
    }

    /**
     * @param bool $export
     * @return mixed
     */
    public function generateOutputData($export = false)
    {
        $grid = $this->get('grid');
        $config = $grid['config'];
        //$config = $this->grid['config'];
        //TODO: add _processFilters and processORM
        //$orm = $this->grid['orm'];
        #$data = $this->grid['orm']->paginate();

        $options = [];
        foreach ($grid['config']['columns'] as $col) {
            if (!empty($col['name']) && !empty($col['options'])) {
                $options[$col['name']] = $col['options'];
            }
        }

        if (isset($config['orm'])) {
            $orm = $config['orm'];
        }

        if (isset($grid['orm'])) {
            $orm = $grid['orm'];
        }

        $data = $this->processORM($orm, null, null, [], $export);

        foreach ($data['rows'] as $row) {
            foreach ($config['columns'] as $col) {
                if (empty($col['name'])) {
                    continue;
                }
                $field = $col['name'];
                $oldValue = $value = $row->get($field);

                if (!empty($options[$field][$value])) {
                    #$value = $options[$field][$value];
                }

                if (!empty($col['cell'])) {
                    switch ($col['cell']) {
                        case 'number':
                            $value = floatval($value);
                            break;
                        case 'integer':
                            $value = intval($value);
                            break;
                    }
                }

                if ($oldValue !== $value) {
                    $row->set($field, $value);
                }
            }
        }
        return $data;
    }

    /**
     * @param BORM $orm
     * @param string $method
     * @param string $stateKey
     * @param array $forceRequest
     * @param bool $export
     * @return mixed
     */
    public function processORM($orm, $method = null, $stateKey = null, $forceRequest = [], $export = false)
    {
        $grid = $this->get('grid');
        $config = $grid['config'];
        $r = $this->BRequest->request();
        if (!empty($r['hash'])) {
            $r = (array)$this->BUtil->fromJson(base64_decode($r['hash']));
        } elseif (!empty($r['filters'])) {
            $r['filters'] = $this->BUtil->fromJson($r['filters']);
        }
        $r = $this->BUtil->arrayMask($r, 's,sd,p,ps,q,filters,hash,gridId');
        $gridId = isset($r['gridId']) ? $r['gridId'] : $grid['config']['id'];
        $pers = $this->FCom_Admin_Model_User->personalize();
        $persState = !empty($pers['grid'][$gridId]['state']) ? $pers['grid'][$gridId]['state'] : [];
        $persState = $this->BUtil->arrayMask($persState, 's,sd,p,ps,q');

        foreach ($persState as $k => $v) {
            if (!isset($r[$k]) && !empty($v)) {
                $r[$k] = $v;
            }
        }

        $filters = $r['filters'];
        $persData = ['grid' => [$gridId => ['state' => $r, 'filters' => $filters]]];
        $this->FCom_Admin_Model_User->personalize($persData);

        if ($stateKey) {
            $sess =& $this->BSession->dataToUpdate();
            $sess['grid_state'][$stateKey] = $r;
        }
        if ($forceRequest) {
            $r = array_replace_recursive($r, $forceRequest);
        }
//print_r($r); exit;
        //$r = array_replace_recursive($hash, $r);


        if (!empty($filters)) {
            $this->_processGridFilters($config, $filters, $orm);
        }
        if (null !== $method) {
            //$this->BEvents->fire('FCom_Admin_View_Grid::processORM', array('orm'=>$orm));
            $this->BEvents->fire($method . ':orm', ['orm' => $orm]);
        }

        //TODO is there any better way to return all rows in paginate function?
        if ($export) {
            $r['p'] = 1;
            $r['ps'] = 1000000;
        }
        $data = $orm->paginate($r);


        $data['filters'] = !empty($filters) ? $filters : null;
        //$data['hash'] = base64_encode($this->BUtil->toJson($this->BUtil->arrayMask($data, 'p,ps,s,sd,q,_search,filters')));
        $data['reloadGrid'] = !empty($r['hash']);
        /*if (!is_null($method)) {
            $this->BEvents->fire($method.':data', array('data'=>&$data));
        }*/
        $this->BEvents->fire(__METHOD__ . ':data', ['data' => &$data]);
        return $data;
    }

    /**
     * @param null|array $params
     * @return string
     */
    public function stateDescription($params = null)
    {
        $descrArr = [];
        if (null === $params) {
            $params = $this->grid['result']['state'];
        }
        if (!empty($params['search'])) {
            $descr = $this->_("Filtered by:") . ' ';
            foreach ($params['search'] as $k => $s) {
                if ($k === '_quick') {
                    $filter = ['type' => 'quick'];
                    $descr .= '<b>' . $this->_('Quick search') . '</b>';
                } else {
                    $filter = $this->grid['config']['filters'][$k];
                    $descr .= '<b>' . $filter['label'] . '</b>';
                }
                switch ($filter['type']) {
                    case 'multiselect':
                        $opts = [];
                        $os = explode(',', $s);
                        if (sizeof($os) == 1) {
                            $descr .= ' ' . $this->_('is <u>%s</u>', $this->q($filter['options'][$os[0]]));
                        } else {
                            foreach ($os as $o) {
                                $opts[] = $filter['options'][$o];
                            }
                            $descr .= ' ' . $this->_('is one of <u>%s</u>', $this->q(join(', ', $opts)));
                        }
                        break;

                    case 'text-range': case 'date-range':
                        $descr .= ' ' . $this->_('is between <u>%s</u> and <u>%s</u>', $this->q($s['from']), $this->q($s['to']));

                        break;
                    case 'quick':
                        $descr .= ' ' . $this->_('by <u>%s</u>', $this->q($s));
                        break;

                    default:
                        $descr .= ' ' . $this->_('contains <u>%s</u>', $this->q($s));
                }
                $descr .= '; ';
            }
            $descrArr[] = $descr;
        }
        return $descrArr ? join("; ", $descrArr) : '';
    }

    /**
     * @param array $config
     * @param $mapColumns
     * @param BORM $orm
     * @param string $when
     */
    protected function _processGridJoins(&$config, &$mapColumns, $orm, $when = 'before_count')
    {
        if (empty($config['join'])) {
            return;
        }
        $mainTableAlias = !empty($config['table_alias']) ? $config['table_alias'] : $config['table'];
        foreach ($config['join'] as $j) {
            if (empty($j['when'])) {
                $j['when'] = 'before_count';
            }
            if ($j['when'] != $when) {
                continue;
            }

            $table = (!empty($j['db']) ? $j['db'] . '.' : '') . $j['table'];
            $tableAlias = isset($j['alias']) ? $j['alias'] : $j['table'];

            $localKey = isset($j['lk']) ? $j['lk'] : 'id';
            $foreignKey = isset($j['fk']) ? $j['fk'] : 'id';

            $localKey = (strpos($localKey, '.') === false ? $mainTableAlias . '.' : '') . $localKey;
            $foreignKey = (strpos($foreignKey, '.') === false ? $tableAlias . '.' : '') . $foreignKey;

            $op = isset($j['op']) ? $j['op'] : '=';


            $joinMethod = (isset($j['type']) ? $j['type'] . '_' : '') . 'join';

            $where = isset($j['where'])
                ? str_replace(
                    ['{lk}', '{fk}', '{lt}', '{ft}'],
                    [$localKey, $foreignKey, $mainTableAlias, $tableAlias],
                    $j['where']
                )
                : [$foreignKey, $op, $localKey];

            $orm->$joinMethod($table, $where, $tableAlias);
        }
    }

    /**
     * @param array $config
     * @param array $filters
     * @param BORM $orm
     */
    protected function _processGridFilters(&$config, $filters, $orm)
    {
        $configFilterFields = [];
        if (!empty($config['filters'])) {
            $indexes = $this->BUtil->arraySeqToMap($config['columns'], 'name', 'index');
            foreach ($filters as $fId => &$f) {
                if (is_array($f)) {
                    $f['field'] = !empty($f['field']) ? $f['field'] : $fId;
                    if (!empty($indexes[$f['field']])) {
                        $f['field'] = $indexes[$f['field']];
                    }
                    if (!preg_match('#^[A-Za-z0-9_.]+$#', $f['field'])) {
                        unset($filters[$fId]);
                    }
                }
            }
            unset($f);
            foreach ($config['filters'] as $fId => $f) {
                if ($fId === '_quick') {
                    if (!empty($f['expr']) && !empty($f['args']) && !empty($filters[$fId])) {
                        $args = [];
                        foreach ($f['args'] as $a) {
                            $args[] = str_replace('?', $filters['_quick'], $a);
                        }
                        $orm->where_raw('(' . $config['filters']['_quick']['expr'] . ')', $args);
                    }
                    break;
                } elseif (!empty($f['field'])) {
                    $configFilterFields[$f['field']] = 1;
                }
            }
        }
        foreach ($filters as $fId => $f) {
            if ($fId === '_quick'
                || !is_array($f)
                || empty($f['type'])
                || !isset($f['val'])
                || $f['val'] === ''
                || (empty($f['val']) && $f['val'] !== 0)
                || empty($configFilterFields[$fId])
            ) {
                continue;
            }

            switch ($f['type']) {
            case 'text':
                $val = $filters[$fId];
                if (!empty($filters[$fId])) {
                    $val = $filters[$fId]['val'];
                    switch ($filters[$fId]['op']) {
                        case 'start'://start with
                            $val = $val . '%';
                            $op = 'like';
                            break;
                        case 'end'://end with
                            $val = '%' . $val;
                            $op = 'like';
                            break;
                        case 'contains'://contain
                            $val = '%' . $val . '%';
                            $op = 'like';
                            break;
                        case 'equal'://equal to
                            $op = 'like';
                            break;
                        case 'not'://does not contain
                            $val = '%' . $val . '%';
                            $op = 'not_like';
                            break;
                    }
                    $this->_processGridFiltersOne($f, $op, $val, $orm);
                }
                break;

            case 'date-range': case 'number-range':
                $val = $filters[$fId]['val'];
                $temp = explode('~', $val);
                if (!empty($filters[$fId])) {
                    switch ($filters[$fId]['op']) {
                        case 'between':
                            $this->_processGridFiltersOne($f, 'gte', $temp[0], $orm);
                            if (isset($temp[1])) {
                                $this->_processGridFiltersOne($f, 'lte', $temp[1], $orm);
                            }
                            break;

                        case 'from':
                            $this->_processGridFiltersOne($f, 'gte', $val, $orm);
                            break;

                        case 'to':
                            $this->_processGridFiltersOne($f, 'lte', $val, $orm);
                            break;

                        case 'equal':
                            if ($f['type'] === 'date-range')
                                $this->_processGridFiltersOne($f, 'like', $val . '%', $orm);
                            else
                                $this->_processGridFiltersOne($f, 'equal', $val, $orm);
                            break;

                        case 'not_in':
                            $orm->where_raw($f['field'] . ' NOT BETWEEN ? and ?', [$temp[0], $temp[1]]);
                            break;
                    }
                }
                break;

            case 'select':
                $this->_processGridFiltersOne($f, 'equal', $filters[$fId]['val'], $orm);
                break;

            case 'multiselect':
                if (!is_array($filters[$fId]['val'])) {
                    $vals = explode(',', $filters[$fId]['val']);
                }
                $this->_processGridFiltersOne($f, 'in', $vals, $orm);
                break;
            }
        }
    }

    /**
     * @param array $filter
     * @param string $op
     * @param string $value
     * @param BORM $orm
     */
    protected function _processGridFiltersOne($filter, $op, $value, $orm)
    {
        $section = !empty($filter['having']) ? 'having' : 'where';
        if (!empty($filter['raw'][$op])) {
            $method = $section . '_raw';
            $orm->$method($filter['raw'][$op], $value);
        } else {
            $method = $section . '_' . $op;
            $orm->$method($filter['field'], $value);
        }
    }

    /**
     * @param array $rows
     * @param null $class
     */
    public function export($rows, $class = null)
    {
        /*if ($class) {
            $this->BEvents->fire($class.'::action_grid_data.orm', array('orm'=>$orm));
        }
        $r = $this->BRequest->request();
        if (!empty($r['filters'])) {
            $r['filters'] = $this->BUtil->fromJson($r['filters']);
        }
        $state = (array)$this->BSession->get('grid_state');
        if ($class && !empty($state[$class])) {
            $r = array_replace_recursive($state[$class], $r);
        }
        if (!empty($r['filters'])) {
            $where = $this->_processFilters($r['filters']);
            $orm->where($where);
        }
        if (!empty($r['s'])) {
            $orm->{'order_by_'.$r['sd']}($r['s']);
        }

        $cfg = $this->BUtil->arrayMerge($this->default_config, $this->config);
        print_r($cfg);exit;
        $cfg = $this->_processConfig($cfg);
        print_r($cfg);
        exit;*/
        $grid = $this->getGrid();
        $columns = $grid['config']['columns'];
        $headers = [];
        foreach ($columns as $i => $col) {
            if (!empty($col['hidden']) && $col['hidden'] !== 'false') continue;
            if (!empty($col['cell']) || $col['name'] === 'thumb_path') continue;
            if ($col['name'] === '_actions') continue;
            $headers[] = !empty($col['label']) ? strtolower($col['label']) : strtolower($col['name']);
            /*if (!empty($col['editoptions']['value']) && is_string($col['editoptions']['value'])) {
                $options = explode(';', $col['editoptions']['value']);
                $col['editoptions']['value'] = array();
                foreach ($options as $o) {
                    list($k, $v) = explode(':', $o);
                    $col['editoptions']['value'][$k] = $v;
                }
                $columns[$i] = $col;
            }*/
        }
        $dir = $this->BApp->storageRandomDir() . '/export';
        $this->BUtil->ensureDir($dir);
        $filename = $dir . '/' . $this->grid['config']['id'] . '.csv';
        $fp = fopen($filename, 'w');
        fwrite($fp, "\xEF\xBB\xBF"); // add UTF8 BOM character to open excel.
        fputcsv($fp, $headers);
        /*$orm->iterate(function($row) use($columns, $fp) {
            if ($class) {
                //TODO: any faster solution?
                $this->BEvents->fire($class.'::action_grid_data.data_row', array('row'=>$row, 'columns'=>$columns));
            }*/


        foreach ($rows as $row) {
            $data = [];

            foreach ($columns as $col) {
                if (!empty($col['hidden']) && $col['hidden'] !== 'false') continue;
                if (!empty($col['cell']) || $col['name'] === 'thumb_path') continue;
                if ($col['name'] === '_actions') continue;
                $k = $col['name'];

                $val = !empty($row->$k) ? $row->$k : '';
//                if (isset($col['options']) && !empty($col['options'])) {
                    if (isset($col['options'][$row->$k])) {
                        $val = $col['options'][$row->$k];
                    }
//                }
                /*if (!empty($col['editoptions']['value'][$val])) {
                    $val = $col['editoptions']['value'][$val];
                }*/
                $data[] = $val;
            }
            fputcsv($fp, $data);
        }/*);*/
        fclose($fp);
        $this->BResponse->sendFile($filename);
    }
}
