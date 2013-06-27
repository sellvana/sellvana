<?php

class FCom_Core_View_Grid extends FCom_Core_View_Abstract
{
    static protected $_defaultActions = array(
        'refresh' => true,
        'link_to_page' => true,
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

    public function pageOptions()
    {
        $url = BRequest::currentUrl();
        $pages = range(1, $this->grid['result']['state']['mp']);
        return array_combine($pages, $pages);
    }

    public function gridActions()
    {
        return $this->grid['config']['actions'];
    }

    public function sortUrl($col)
    {
        $grid = $this->get('grid');
        if (!empty($col['no_sort'])) {
            return '#';
        }
        if (!empty($grid['request']['s']) && $grid['request']['s']==$col['id']) {
            $change = array('sd'=>$grid['request']['sd']=='desc'?'asc':'desc');
        } else {
            $change = array('s'=>$col['id'], 'sd'=>'asc');
        }
        return $this->gridUrl($change);
    }

    public function sortClass($col, $s = null)
    {
        if (!$s) {
            $s = $this->grid['result']['state'];
        }
        return !empty($s['s']) && $s['s'] == $col['id'] ? 'sort-'.$s['sd'] : '';
    }

    public function colFilterHtml($col)
    {
        return '';
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
        foreach ($c['columns'] as $cId=>&$col) {
            $col['id'] = $cId;
            if (!empty($col['fields'])) {
                foreach ($col['fields'] as $fId) {
                    $c['fields'][$fId]['col'] = $cId;
                }
            }
        }
        unset($col);

        if (!empty($c['actions'])) {
            foreach ($c['actions'] as $k => &$action) {
                if (true === $action && !empty(static::$_defaultActions[$k])) {
                    switch ($k) {
                        case 'refresh':
                            $action = array('html' => BUtil::tagHtml('a',
                                array('href' => BRequest::currentUrl(), 'class' => 'js-change-url grid-refresh'),
                                BLocale::_('Refresh')
                            ));
                            break;

                        case 'link_to_page':
                            $action = array('html' => BUtil::tagHtml('a',
                                array('href' => BRequest::currentUrl(), 'class' => 'grid-link_to_page'),
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
        $config = $this->gridConfig();
        $grid = $this->grid;

        // fetch request parameters
        if (empty($grid['request'])) {
            $grid['request'] = BRequest::i()->get();
        }

        $orm = $config['orm'];

        BEvents::i()->fire(__METHOD__.'.initORM: '.$config['id'], array('orm'=>$orm, 'grid'=>$grid));

        $mapColumns = array();

        //$this->_processGridJoins($config, $mapColumns, $orm, 'before_count');
        $this->_processGridFilters($config, BRequest::i()->get('filter'), $orm);

        $grid['result'] = $orm->paginate($grid['request'], array(
            's' => !empty($config['sort']) ? $config['sort'] : null,
            'sd' => !empty($config['sort_dir']) ? $config['sort_dir'] : null,
            'p' => !empty($config['page']) ? $config['page'] : null,
            'ps' => !empty($config['page_size']) ? $config['page_size'] : $config['page_size_options'][0],
        ));
        $grid['result']['state']['description'] = $this->stateDescription($grid['result']['state']);

        BEvents::i()->fire(__METHOD__.'.after: '.$config['id'], array('grid'=>&$grid));

        $this->grid = $grid;
        return $grid;
    }

    public function rowsHtml()
    {
        $grid = $this->get('grid');
        $rows = $grid['result']['rows'];
        $gridId = $grid['config']['id'];
        $columns = $grid['config']['columns'];

        $trHtmlArr = array();
        foreach ($rows as $rowId => $row) {
            $row->_id = $rowId;
            $trAttr = array();
            $trAttr['id'] = "data-row--{$gridId}--{$rowId}";
            $trAttr['class'][] = $rowId % 2 ? 'odd' : 'even';

            $tdHtmlArr = array();
            foreach ($columns as $colId => $col) {
                $cellData = $this->cellData($row, $col);
                $tdHtmlArr[] = BUtil::tagHtml('td', $cellData['attr'], $cellData['html']);
                if (!empty($cellData['row_attr'])) {
                    $trAttr = array_merge_recursive($cellData['row_attr']);
                }
            }
            $trHtmlArr[] = BUtil::tagHtml('tr', $trAttr, join("\n", $tdHtmlArr));
        }
        return join("\n", $trHtmlArr);
    }

    public function cellData($row, $col)
    {
        $grid = $this->get('grid');
        $args = array('grid' => $grid, 'row' => $row, 'col' => $col);
        $out = array();

        if (empty($col['attr'])) {
            $out['attr'] = array();
        } elseif (is_callable($col['attr'])) {
            $out['attr'] = call_user_func($col['attr'], $args);
        } else {
            $out['attr'] = (array)$row['attr'];
        }
        if (empty($out['attr']['id'])) {
            $out['attr']['id'] = "data-cell--{$grid['config']['id']}--{$row->_id}--{$col['id']}";
        }

        $value = $row->get($col['id']);

        if (('' === $value || is_null($value)) && !empty($col['default'])) {
            $value = $col['default'];
        }

        if (isset($col['options'][$value])) {
            $value = $col['options'][$value];
        }

        if (!empty($col['format'])) {
            if (is_string($col['format'])) {
                switch ($col['format']) {
                    case 'boolean': $value = $value ? 1 : 0; break;
                    case 'date': $value = $value ? BLocale::i()->datetimeDbToLocal($value) : ''; break;
                    case 'currency': $value = $value ? '$'.number_format($value, 2) : ''; break;
                    default: BDebug::warning('Grid value format not implemented: '.$col['format']);
                }
            } elseif (is_callable($col['format'])) {
                $args['value'] = $value;
                $value = call_user_func($col['format'], $args);
            }
        }

        $html = nl2br($this->q($value));

        if (!empty($col['href'])) {
            $html = BUtil::tagHtml('a', array('href' => BUtil::injectVars($col['href'], $row->as_array())), $html);
        }

        $out['html'] = $html;

        if (!empty($col['row_attr']) && is_callable($col['row_attr'])) {
            $out['row_attr'] = call_user_func($out['row_attr'], $args);
        }
        return $out;
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
