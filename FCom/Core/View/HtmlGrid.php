<?php

class FCom_Core_View_HtmlGrid extends FCom_Core_View_Abstract
{
    static protected $_defaultActions = array(
        'refresh' => true,
        'link_to_page' => true,
        'columns' => true,
    );

    public function gridUrl($changeRequest=array())
    {
        if (!$changeRequest) {
            return $this->grid['config']['grid_url'];
        }
        return BUtil::setUrlQuery($this->grid['config']['grid_url'], $changeRequest);
    }

    public function pageSizeHref()
    {
        return BUtil::setUrlQuery(true, array('ps' => '-VALUE-'));
    }

    public function pageSizeOptions()
    {
        $pageSizes = $this->grid['config']['page_size_options'];
        return array_combine($pageSizes, $pageSizes);
    }

    public function pageChangeHref()
    {
        return BUtil::setUrlQuery(true, array('p' => '-VALUE-'));
    }

    public function gridActions()
    {
        if (empty($this->grid['config']['actions'])) {
            return array();
        }
        return $this->grid['config']['actions'];
    }

    public function callUserFunc($cb, $args)
    {
        return call_user_func_array($cb, $args);
    }

    public function multiselectToggleOptions()
    {
        return array(
            '' => '',
            '@Show' => array(
                'show_all' => 'All',
                'show_sel' => 'Sel',
                'show_unsel' => 'Unsel',
            ),
            '@Select' => array(
                'upd_sel' => 'Sel',
                'upd_unsel' => 'Unsel',
            ),
        );
    }

    public function multiselectCurrent()
    {
        $grid = $this->get('grid');
        return !empty($grid['request']['selected']) ? $grid['request']['selected'] : '';
    }

    public function sortHref($col)
    {
        $grid = $this->get('grid');
        if (empty($col['name']) || isset($col['sortable']) && !$col['sortable']) {
            return '#';
        }
        if (!empty($grid['request']['s']) && $grid['request']['s']==$col['name']) {
            $change = array('sd'=>$grid['request']['sd']=='desc'?'asc':'desc');
        } else {
            $change = array('s'=>$col['name'], 'sd'=>'asc');
        }
        return BUtil::setUrlQuery(true, $change);
    }

    public function sortStyle($col)
    {
        return !empty($col['width']) ? "width:{$col['width']}px" : '';
    }

    public function sortClass($col)
    {
        $classArr = array();
        if (empty($col['no_reorder'])) $classArr[] = 'js-draggable';

        $s = $this->grid['result']['state'];
        if (!empty($s['s']) && !empty($col['name']) && $s['s'] == $col['name']) {
            $classArr[] = 'sort-'.$s['sd'];
        } else {
            $classArr[] = 'sort';
        }

        return join(' ', $classArr);
    }

    public function colFilterHtml($col)
    {
        return '';
    }

    protected function _processDefaults()
    {
        //TODO: remember processed config
        $grid = $this->grid;
        $c =& $grid['config'];

        if (empty($c['grid_url'])) {
            $c['grid_url'] = BRequest::currentUrl();
        }
        if (empty($c['page_size_options'])) {
            $c['page_size_options'] = array(10, 25, 50, 100);
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
            $grid['request'] = BRequest::i()->get();
        }

        $this->grid = $grid;
    }

    protected function _processColumnsConfig()
    {
        $grid = $this->grid;
        $pos = 0;
        foreach ($grid['config']['columns'] as $cId => &$col) {
            if (empty($col['name'])) {
                $col['name'] = $cId;
            }
            $col['position'] = ++$pos;
            switch ($cId) {
                case '_multiselect':
                    $col['type'] = 'multiselect';
                    $col['width'] = 50;
                    $col['no_reorder'] = true;
                    $col['format'] = function($args) {
                        return BUtil::tagHtml('input', array(
                            'type'  => 'checkbox',
                            //'name'  => "grid[{$args['grid']['config']['id']}][sel][{$args['row']->id}]",
                            'class' => 'js-sel',
                        ));
                    };
                    break;

                case '_actions':
                    $col['type'] = 'actions';
                    $col['label'] = 'Actions';
                    //$col['width'] = 50;
                    $col['no_reorder'] = true;
                    $col['format'] = function($args) use($col) {
                        $options = array('' => '');
                        if (!empty($col['options'])) {
                            foreach ($col['options'] as $k => $opt) {
                                if (!empty($opt['data-href'])) {
                                    $opt['data-href'] = BUtil::injectVars($opt['data-href'], $args['row']->as_array());
                                }
                                $options[$k] = $opt;
                            }
                        }
                        return BUtil::tagHtml('select', array('class'=>'js-actions'), BUtil::optionsHtml($options));
                    };
                    break;
            }
        }
        unset($col);
        $this->grid = $grid;
    }

    protected function _processActionsConfig()
    {
        if (empty($this->grid['config']['actions'])) {
            return;
        }
        $grid = $this->grid;

        foreach ($grid['config']['actions'] as $k => &$action) {
            if (true === $action && !empty(static::$_defaultActions[$k])) {
                switch ($k) {
                    case 'refresh':
                        $action = array('html' => BUtil::tagHtml('a',
                            array('href' => BRequest::currentUrl(), 'class' => 'js-change-url grid-refresh btn'),
                            BLocale::_('Refresh')
                        ));
                        break;
                    case 'link_to_page':
                        $action = array('html' => BUtil::tagHtml('a',
                            array('href' => BRequest::currentUrl(), 'class' => 'grid-link_to_page btn'),
                            BLocale::_('Link')
                        ));
                        break;

                    default:
                        $action = static::$_defaultActions[$k];
                }
            }
            if (is_string($action)) {
                $action = array('html' => $action);
            }
        }
        unset($action);
        $this->grid = $grid;
    }

    protected function _personalizePageState($state)
    {
        return $state;
    }

    protected function _processPersonalization()
    {
        $grid = $this->grid;
        $gridId = !empty($grid['personalize']['id']) ? $grid['personalize']['id'] : $grid['config']['id'];

        // retrieve current personalization
        $pers = FCom_Admin_Model_User::i()->personalize();
        $persGrid = !empty($pers['grid'][$gridId]) ? $pers['grid'][$gridId] : array();
#var_dump($pers);
        $req = BRequest::i()->get();

        // prepare array to update personalization

        $personalize = array();
        foreach (array('p', 'ps', 's', 'sd', 'q') as $k) {
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
            FCom_Admin_Model_User::i()->personalize(array('grid' => array($gridId => $personalize)));
        }

        // get columns personalization
        $persCols = array();
        $defPos = 0;
        foreach ($grid['config']['columns'] as $col) {
            if (!empty($col['name']) && !empty($persGrid['columns'][$col['name']])) {
                $col = BUtil::arrayMerge($col, $persGrid['columns'][$col['name']]);
            }
            if (empty($col['position'])) {
                $col['position'] = $defPos;
            }
            $defPos++;
            $persCols[] = $col;
        }
        usort($persCols, function($a, $b) { return $a['position'] - $b['position']; });
        $grid['config']['columns'] = $persCols;

        $this->grid = $grid;
    }

    protected function _resetPersonalization()
    {
        $grid = $this->grid;
        $gridId = !empty($grid['personalize']['id']) ? $grid['personalize']['id'] : $grid['config']['id'];
        $reset = array('state' => array('p' => null, 'ps' => null, 's' => null, 'sd' => null, 'q' => null));
        FCom_Admin_Model_User::i()->personalize(array('grid' => array($gridId => $reset)));
    }

    public function getGrid()
    {
        if (!empty($this->grid['_processed'])) {
            return $this->grid;
        }

        $this->_processDefaults();
        $this->_processColumnsConfig();
        $this->_processActionsConfig();
        $this->_processPersonalization();

        $grid = $this->grid;
        BEvents::i()->fire(__METHOD__.'.after', array('grid' => &$grid));
        $grid['_processed'] = true;
        $this->grid = $grid;

        return $grid;
    }

    public function getGridData(array $options=array())
    {
        // fetch grid configuration
        $grid = $this->getGrid();
        $config = $grid['config'];

        if (empty($config['orm']) && !$config['data']) {
            throw new BException('Either ORM or data is required');
        }

        if (isset($config['data']) && !empty($config['data'])) {
            $grid['result']['state'] = array(); //todo: add pagination for reserved data
            $grid['result']['rows'] = $config['data'];
        } elseif (!empty($config['orm'])) {
            $orm = $config['orm'];
            if (is_string($orm)) {
                $orm = $orm::i()->orm();
            }
            BEvents::i()->fire(__METHOD__.'.initORM: '.$config['id'], array('orm'=>$orm, 'grid'=>$grid));

            $this->_processGridFilters($config, BRequest::i()->get('filter'), $orm);
            try {
                $grid['result'] = $orm->paginate($grid['request'], array(
                    's'  => !empty($config['state']['s'])  ? $config['state']['s']  : null,
                    'sd' => !empty($config['state']['sd']) ? $config['state']['sd'] : null,
                    'p'  => !empty($config['state']['p'])  ? $config['state']['p']  : null,
                    'ps' => !empty($config['state']['ps']) ? $config['state']['ps'] : $config['page_size_options'][0],
                ));
            } catch (Exception $e) {
                $this->_resetPersonalization();
                throw $e;
            }
            //var_dump($grid['result']);exit;
            $grid['result']['state']['description'] = $this->stateDescription($grid['result']['state']);
            BEvents::i()->fire(__METHOD__.'.after: '.$config['id'], array('grid' =>& $grid));
        }

        $this->grid = $grid;
        return $grid;
    }

    public function getPageRowsData()
    {
        $grid = $this->get('grid');
        $state = $grid['result']['state'];
        $rows = $grid['result']['rows'];
        $gridId = $grid['config']['id'];

        $pers = FCom_Admin_Model_User::i()->personalize();
        $persState = !empty($pers['grid'][$gridId]['state']) ? $pers['grid'][$gridId]['state'] : array();
        $persState = BUtil::arrayMask($persState, 's,sd,p,ps,q');
        foreach ($persState as $k => $v) {
            if (!empty($v)) {
                $state[$k] = $v;
            }
        }

        $data = array();
        foreach ($rows as $rowId => $row) {
            $data[] = (!is_array($row)) ? $row->as_array() : $row;
        }

        return json_encode(array('state' => $state, 'data' => $data));
    }

    public function getColumnsData()
    {
        $grid = $this->get('grid');
        return json_encode($grid['config']['columns']);
    }

    public function getPageHtmlData($rows = null)
    {
        $grid = $this->get('grid');
        if (is_null($rows)) {
            $rows = $grid['result']['rows'];
        }
        $gridId = $grid['config']['id'];
        $columns = $grid['config']['columns'];

        $trArr = array();
        foreach ($rows as $rowId => $row) {
            $row->_id = $rowId;
            $trAttr = array();
            $trAttr['id'] = "data-row--{$gridId}--{$rowId}";
            $trAttr['data-id'] = $row->get($grid['config']['row_id_column']);
            $trAttr['class'][] = $rowId % 2 ? 'odd' : 'even';

            $tdArr = array();
            foreach ($columns as $colId => $col) {
                $cellData = $this->cellData($row, $col);
                $tdArr[$colId] = array('attr' => $cellData['attr'], 'html' => $cellData['html']);
                if (!empty($cellData['row_attr'])) {
                    $trAttr = array_merge_recursive($cellData['row_attr']);
                }
            }
            $trArr[$rowId] = array('attr' => $trAttr, 'cells' =>$tdArr);
        }

        if (!empty($grid['config']['format_callback'])) {
            $cb = $grid['config']['format_callback'];
            if (is_callable($cb)) {
                call_user_func($cb, array('grid' => $grid, 'rows' => &$trArr));
            } else {
                BDebug::warning('Invalid grid format_callback');
            }
        }
        return $trArr;
    }

    public function rowsHtml($rows = null)
    {
        $trArr = $this->getPageHtmlData($rows);

        $trHtmlArr = array();
        foreach ($trArr as $rowId => $tr) {
            $tdHtmlArr = array();
            foreach ($tr['cells'] as $colId => $cell) {
                $tdHtmlArr[] = BUtil::tagHtml('td', $cell['attr'], $cell['html']);
            }
            $trHtmlArr[] = BUtil::tagHtml('tr', $tr['attr'], join("\n", $tdHtmlArr));
        }

        return join("\n", $trHtmlArr);
    }

    public function cellData($row, $col)
    {
        $grid = $this->get('grid');
        $args = array('grid' => $grid, 'row' => $row, 'col' => $col);
        $out = array();

        $out['attr'] = !empty($col['attr']) ? $col['attr'] : array();
        if (!empty($col['attr_callback'])) {
            $args['attr'] = $out['attr'];
            $out['attr'] = call_user_func($col['attr_callback'], $args);
        }
        if (empty($col['name'])) {
            $col['name'] = null; //TODO: correct value
        }
        $out['attr']['data-col'] = $col['name'];
        //$out['attr']['id'] = "data-cell--{$grid['config']['id']}--{$row->_id}--{$col['id']}";

        $field = !empty($col['field']) ? $col['field'] : $col['name'];
        $value = $row->get($field);

        if (('' === $value || is_null($value)) && !empty($col['default'])) {
            $value = $col['default'];
        }

        $out['attr']['data-value'] = $value;

        if (isset($col['options'][$value])) {
            $value = $col['options'][$value];
        }

        if (!empty($col['format'])) {
            if (is_string($col['format'])) {
                switch ($col['format']) {
                    case 'boolean': $value = $value ? 1 : 0; break;
                    case 'date': $value = $value ? BLocale::i()->datetimeDbToLocal($value) : ''; break;
                    case 'datetime': $value = $value ? BLocale::i()->datetimeDbToLocal($value, true) : ''; break;
                    case 'currency': $value = $value ? '$'.number_format($value, 2) : ''; break;
                    default: BDebug::warning('Grid value format not implemented: '.$col['format']);
                }
                $value = nl2br($this->q($value));
            } elseif (is_callable($col['format'])) {
                $args['value'] = $value;
                $value = call_user_func($col['format'], $args);
            }
        }

        if (!empty($col['row_attr_callback']) && is_callable($col['row_attr_callback'])) {
            $out['row_attr'] = call_user_func($out['row_attr_callback'], $args);
        }

        if (!empty($col['href'])) {
            $value = BUtil::tagHtml('a', array('href' => BUtil::injectVars($col['href'], $row->as_array())), $value);
        }

        $out['html'] = $value;

        return $out;
    }

    public function outputData()
    {
        $config = $this->grid['config'];
        //TODO: add _processFilters and processORM
        $orm = $this->grid['orm'];
        #$data = $this->grid['orm']->paginate();

        $data = $this->processORM($this->grid['orm']);

        foreach ($data['rows'] as $row) {
            foreach ($config['columns'] as $col) {
                if (!empty($col['cell']) && !empty($col['name'])) {
                    $field = $col['name'];
                    $value = $row->get($field);
                    switch ($col['cell']) {
                        case 'number':
                            $value1 = floatval($value);
                            break;
                        case 'integer':
                            $value1 = intval($value);
                            break;
                    }
                    if ($value !== $value1) {
                        $row->set($field, $value1);
                    }
                }
            }
        }
        return $data;
    }

    public function processORM($orm, $method=null, $stateKey=null, $forceRequest=array())
    {
        $r = BRequest::i()->request();
        if (!empty($r['hash'])) {
            $r = (array)BUtil::fromJson(base64_decode($r['hash']));
        } elseif (!empty($r['filters'])) {
            $r['filters'] = BUtil::fromJson($r['filters']);
        }
        $r = BUtil::arrayMask($r, 's,sd,p,ps,q');

        $gridId = $this->grid['config']['id'];
        $pers = FCom_Admin_Model_User::i()->personalize();
        $persState = !empty($pers['grid'][$gridId]['state']) ? $pers['grid'][$gridId]['state'] : array();
        $persState = BUtil::arrayMask($persState, 's,sd,p,ps,q');
        foreach ($persState as $k => $v) {
            if (!isset($r[$k]) && !empty($v)) {
                $r[$k] = $v;
            }
        }

        FCom_Admin_Model_User::i()->personalize(array('grid' => array($gridId => array('state' => $r))));

        if ($stateKey) {
            $sess =& BSession::i()->dataToUpdate();
            $sess['grid_state'][$stateKey] = $r;
        }
        if ($forceRequest) {
            $r = array_replace_recursive($r, $forceRequest);
        }
//print_r($r); exit;
        //$r = array_replace_recursive($hash, $r);

        if (!empty($r['filters'])) {
            $where = $this->_processFilters($r['filters']);
            $orm->where($where);
        }
        if (!is_null($method)) {
            //BEvents::i()->fire('FCom_Admin_View_Grid::processORM', array('orm'=>$orm));
            BEvents::i()->fire($method.'.orm', array('orm'=>$orm));
        }

        $data = $orm->paginate($r);
        //print_r($r);

        $data['filters'] = !empty($r['filters']) ? $r['filters'] : null;
        //$data['hash'] = base64_encode(BUtil::toJson(BUtil::arrayMask($data, 'p,ps,s,sd,q,_search,filters')));
        $data['reloadGrid'] = !empty($r['hash']);
        if (!is_null($method)) {
            BEvents::i()->fire($method.'.data', array('data'=>&$data));
        }

        return $data;
    }

    public function stateDescription($params=null)
    {
        $descrArr = array();
        if (is_null($params)) {
            $params = $this->grid['result']['state'];
        }
        if (!empty($params['search'])) {
            $descr = $this->_("Filtered by:").' ';
            foreach ($params['search'] as $k=>$s) {
                if ($k==='_quick') {
                    $filter = array('type'=>'quick');
                    $descr .= '<b>'.$this->_('Quick search').'</b>';
                } else {
                    $filter = $this->grid['config']['filters'][$k];
                    $descr .= '<b>'.$filter['label'].'</b>';
                }
                switch ($filter['type']) {
                    case 'multiselect':
                        $opts = array();
                        $os = explode(',', $s);
                        if (sizeof($os)==1) {
                            $descr .= ' '.$this->_('is <u>%s</u>', $this->q($filter['options'][$os[0]]));
                        } else {
                            foreach ($os as $o) {
                                $opts[] = $filter['options'][$o];
                            }
                            $descr .= ' '.$this->_('is one of <u>%s</u>', $this->q(join(', ', $opts)));
                        }
                        break;

                    case 'text-range': case 'date-range':
                        $descr .= ' '.$this->_('is between <u>%s</u> and <u>%s</u>', $this->q($s['from']), $this->q($s['to']));

                        break;
                    case 'quick':
                        $descr .= ' '.$this->_('by <u>%s</u>', $this->q($s));
                        break;

                    default:
                        $descr .= ' '.$this->_('contains <u>%s</u>', $this->q($s));
                }
                $descr .= '; ';
            }
            $descrArr[] = $descr;
        }
        return $descrArr ? join("; ", $descrArr) : '';
    }

    protected function _processGridJoins(&$config, &$mapColumns, $orm, $when='before_count')
    {
        if (empty($config['join'])) {
            return;
        }
        $mainTableAlias = !empty($config['table_alias']) ? $config['table_alias'] : $config['table'];
        foreach ($config['join'] as $j) {
            if (empty($j['when'])) {
                $j['when'] = 'before_count';
            }
            if ($j['when']!=$when) {
                continue;
            }

            $table = (!empty($j['db']) ? $j['db'].'.' : '').$j['table'];
            $tableAlias = isset($j['alias']) ? $j['alias'] : $j['table'];

            $localKey = isset($j['lk']) ? $j['lk'] : 'id';
            $foreignKey = isset($j['fk']) ? $j['fk'] : 'id';

            $localKey = (strpos($localKey, '.')===false ? $mainTableAlias.'.' : '').$localKey;
            $foreignKey = (strpos($foreignKey, '.')===false ? $tableAlias.'.' : '').$foreignKey;

            $op = isset($j['op']) ? $j['op'] : '=';


            $joinMethod = (isset($j['type']) ? $j['type'].'_' : '').'join';

            $where = isset($j['where']) ? str_replace(array('{lk}', '{fk}', '{lt}', '{ft}'), array($localKey, $foreignKey, $mainTableAlias, $tableAlias), $j['where']) : array($foreignKey, $op, $localKey);

            $orm->$joinMethod($table, $where, $tableAlias);

            /*
            if (!empty($j['select'])) {
                list($localTable, ) = explode('.', $localKey);
                foreach ($j['select'] as $jm) {
                    $fieldAlias = !empty($jm['alias']) ? $jm['alias'] : null;
                    if (isset($jm['field'])) {
                        $orm->select($tableAlias.'.'.$jm['field'], $fieldAlias);
                    } elseif (isset($jm['expr'])) {
                        $expr = str_replace(array('{lt}', '{ft}'), array($localTable, $tableAlias), $jm['expr']);
                        $orm->select_expr($expr, $fieldAlias);
                    }
                }
            }

            if (!empty($j['where'])) {
                $orm->where_raw($j['where'][0], $j['where'][1]);
            }
            */
        }
    }

    protected function _processGridFilters(&$config, $filters, $orm)
    {
        if (empty($config['filters'])) {
            return;
        }
        foreach ($config['filters'] as $fId=>$f) {
            $f['field'] = !empty($f['field']) ? $f['field'] : $fId;

            if ($fId=='_quick') {
                if (!empty($f['expr']) && !empty($f['args']) && !empty($filters[$fId])) {
                    $args = array();
                    foreach ($f['args'] as $a) {
                        $args[] = str_replace('?', $filters['_quick'], $a);
                    }
                    $orm->where_raw('('.$config['filters']['_quick']['expr'].')', $args);
                }
                continue;
            }
            if (!empty($f['type'])) switch ($f['type']) {
            case 'text':
                if (!empty($filters[$fId])) {
                    $this->_processGridFiltersOne($f, 'like', $filters[$fId].'%', $orm);
                }
                break;

            case 'text-range': case 'number-range': case 'date-range':
                if (!empty($filters[$fId]['from'])) {
                    $this->_processGridFiltersOne($f, 'gte', $filters[$fId]['from'], $orm);
                }
                if (!empty($filters[$fId]['to'])) {
                    $this->_processGridFiltersOne($f, 'lte', $filters[$fId]['to'], $orm);
                }
                break;

            case 'select':
                if (!empty($filters[$fId])) {
                    $this->_processGridFiltersOne($f, 'equal', $filters[$fId], $orm);
                }
                break;

            case 'multiselect':
                if (!empty($filters[$fId])) {
                    $filters[$fId] = explode(',', $filters[$fId]);
                    $this->_processGridFiltersOne($f, 'in', $filters[$fId], $orm);
                }
                break;
            }
        }
    }

    protected function _processGridFiltersOne($filter, $op, $value, $orm)
    {
        if (!empty($filter['raw'][$op])) {
            $orm->where_raw($filter['raw'][$op], $value);
        } else {
            $method = 'where_'.$op;
            $orm->$method($filter['field'], $value);
        }
    }

}
