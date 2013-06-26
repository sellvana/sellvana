<?php

class FCom_Core_View_Grid extends FCom_Core_View_Abstract
{
    static protected $_defaultActions = array(
        'refresh' => true,
    );

    public function gridUrl($changeRequest=array())
    {
        return BUtil::setUrlQuery($this->grid['config']['grid_url'], $changeRequest);
    }

    public function sortUrl($colId)
    {
        if (!empty($this->grid['request']['s']) && $this->grid['request']['s']==$colId) {
            $change = array('sd'=>$this->grid['request']['sd']=='desc'?'asc':'desc');
        } else {
            $change = array('s'=>$colId, 'sd'=>'asc');
        }
        return $this->gridUrl($change);
    }

    public function pageSizeOptions()
    {
        $options = $this->grid['config']['page_size_options'];
        $options = array_combine($options, $options);
        return $options;
    }

    public function gridActions()
    {
        return $this->grid['config']['actions'];
    }

    public function sortClass($colId)
    {
        $s = $this->grid['result']['state'];
        return !empty($s['s']) && $s['s']==$colId ? 'sort-'.$s['sd'] : '';
    }

    public function colFilterHtml($colId)
    {
        return '';
    }

    public function cellStyle($row, $colId)
    {
        $column = $this->grid['config']['columns'][$colId];
        if (empty($column['style'])) {
            return '';
        }
        return is_callable($column['style']) ? call_user_func($column['style'], $row, $colId) : $column['style'];
    }

    public function cellClass($row, $colId)
    {
        return !empty($row[$colId]['class']) ? $row[$colId]['class'] : '';
    }

    public function cellHtml($row, $colId)
    {
        return $row[$colId]['raw'];
    }

    public function cellData($cell, $rowId=null, $colId=null)
    {
        /*
        if (empty($this->grid['data']['rows'][$rowId][$colId])) {
            return '';
        }
        $cell = $this->grid['data']['rows'][$rowId][$colId];
        */
        if (!empty($this->grid['config']['columns'][$colId]['renderer'])) {
            $renderer = $this->grid['config']['columns'][$colId]['renderer'];
            if (is_callable($renderer)) {
                return call_user_func($renderer, $cell, $rowId, $colId);
            }
        }

        return nl2br($this->q(!empty($cell['value']) ? $cell['value'] : ''));
    }

    public function gridConfig()
    {
        //TODO: remember processed config
        $c = $this->grid['config'];

        if (empty($c['grid_url'])) {
            $c['grid_url'] = BRequest::currentUrl();
        }
        if (empty($c['page_size_options'])) {
            $c['page_size_options'] = array(1, 25, 50, 100);
        }
        if (empty($c['page_size'])) {
            $c['page_size'] = $c['page_size_options'][0];
        }
        if (empty($c['search'])) {
            $c['search'] = new stdClass;
        }
        if (!isset($c['sort'])) {
            $c['sort'] = '';
        }
        if (!isset($c['sort_dir'])) {
            $c['sort_dir'] = 'asc';
        }
        if (empty($c['fields'])) {
            $c['fields'] = $c['columns'];
            foreach ($c['columns'] as $cId=>$col) {
                $c['columns'][$cId]['fields'] = array($cId);
            }
        }
        foreach ($c['columns'] as $cId=>$col) {
            if (!empty($col['fields'])) {
                foreach ($col['fields'] as $fId) {
                    $c['fields'][$fId]['col'] = $cId;
                }
            }
        }
        if (!empty($c['actions'])) {
            foreach ($c['actions'] as $k => &$action) {
                if (true === $action && !empty(static::$_defaultActions[$action])) {
                    switch ($k) {
                        case 'refresh':
                            $action = BUtil::tagHtml('a',
                                array('href' => BRequest::currentUrl(), 'class' => 'js-change-url grid-refresh'),
                                BLocale::_('Refresh')
                            );
                            break;

                        default:
                            $action = static::$_defaultActions[$action];
                    }
                }
                if (is_string($action)) {
                    $action = array('html' => $action);
                }
            }
            unset($action);
        }
        BEvents::i()->fire(__METHOD__.'.after', array('config' => &$c));

        $grid = $this->grid;
        $grid['config'] = $c;
        $this->grid = $grid;
        return $c;
    }

    public function gridData(array $options=array())
    {
        // fetch grid configuration
        $grid = $this->grid;
        /*
        $config =& $grid['config'];
        if (!empty($grid['serverConfig'])) {
            $config = BUtil::arrayMerge($config, $grid['serverConfig']);
        }
        */
        $config = $this->grid['config'];

        // fetch request parameters
        if (empty($grid['request'])) {
            $grid['request'] = BRequest::i()->get();
        }

        $orm = $config['orm'];

        BEvents::i()->fire('BViewGrid::gridData.initORM: '.$config['id'], array('orm'=>$orm, 'grid'=>$grid));

        $mapColumns = array();

        //$this->_processGridJoins($config, $mapColumns, $orm, 'before_count');
        $this->_processGridFilters($config, BRequest::i()->get('filter'), $orm);

        $result = $orm->paginate(null, array(
            's' => !empty($config['sort']) ? $config['sort'] : null,
            'sd' => !empty($config['sort_dir']) ? $config['sort_dir'] : null,
            'ps' => !empty($config['page_size']) ? $config['page_size'] : null,
        ));
        $state['description'] = $this->stateDescription($result['state']);
        $grid['result'] = array(
            'state' => $result['state'],
            'raw' => empty($options['no_out']) ? $result['rows'] : null,
            'out' => array(),
            //'query' => $orm,
        );

        foreach ($result['rows'] as $i => $model) {
            $r = $model->as_array();
            if (empty($options['no_raw'])) {
                //$grid['result']['raw'][$i] = $r;
            }
            if (empty($options['no_out'])) {
                foreach ($config['columns'] as $k=>$f) {
                    $field = !empty($f['field']) ? $f['field'] : $k;
                    $grid['result']['out'][$i][$k]['raw'] = isset($r[$field]) ? $r[$field] : null;
                    $value = isset($r[$field]) ? $r[$field] : (isset($f['default']) ? $f['default'] : '');
                    if (!empty($f['options'][$value])) $value = $f['options'][$value];
                    if (!empty($f['format'])) {
                        $value = $this->_formatGridValue($f['format'], $value);
                    }
                    $grid['result']['out'][$i][$k]['value'] = $value;
                    if (!empty($f['href'])) {
                        $grid['result']['out'][$i][$k]['href'] = BUtil::injectVars($f['href'], $r);
                    }
                }
                if (!empty($config['map'])) {
                    foreach ($config['map'] as $m) {
                        $value = $r[$m['value']];
                        if (!empty($m['format'])) {
                            $value = $this->_formatGridValue($m['format'], $value);
                        }
                        $grid['result']['out'][$i][$m['field']][$m['prop']] = $value;
                    }
                }
            }
        }
        BEvents::i()->fire('BGridView::gridData.after: '.$config['id'], array('grid'=>&$grid));

        $this->grid = $grid;
        return $grid;
    }

    protected function _formatGridValue($format, $value)
    {
        if (is_string($format)) {
            switch ($format) {
                case 'boolean': $value = !!$value; break;
                case 'date': $value = $value ? BLocale::i()->datetimeDbToLocal($value) : ''; break;
                case 'currency': $value = $value ? '$'.number_format($value, 2) : ''; break;
                default: BDebug::warning('Grid value format not implemented: '.$format);
            }
        } elseif (is_callable($format)) {
            $value = $format($value);
        }
        return $value;
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
}
