<?php

abstract class FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    protected $_filterOpsByType = [
        'text' => [
            'equals' => 'equals',
            'contains' => 'contains',
            'not_contains' => 'does not contain',
            'starts' => 'starts with',
            'ends' => 'ends with',
            'empty' => 'is empty',
        ],
        'select' => [
            'in' => 'is one of',
            'not_in' => 'is not one of',
            'empty' => 'is empty',
        ],
        'number' => [
            'equals' => 'equals',
            'in' => 'is one of',
            'not_in' => 'is not one of',
            'between' => 'between',
            'not_between' => 'not between',
            'lt' => 'less than',
            'lte' => 'less than or equal',
            'gt' => 'greater than',
            'gte' => 'greater than or equal',
            'empty' => 'is empty',
        ],
        'date' => [
            'equals' => 'equals',
            'between' => 'between',
            'not between' => 'not between',
            'lt' => 'before',
            'gt' => 'after',
            'empty' => 'is empty',
        ],
    ];

    protected $_filterDefaultOpByType = [
        'text' => 'contains',
        'select' => 'in',
        'number' => 'between',
        'date' => 'between',
    ];

    /**
     * @return array
     */
    abstract public function getGridConfig();

    /**
     * @return BORM
     */
    abstract public function getGridOrm();

    public function action_grid_config()
    {
        $config = $this->getGridConfig();
        $config = $this->normalizeGridConfig($config);
        $this->respond($config);
    }

    public function action_grid_data()
    {
        $config = $this->getGridConfig();
        $orm = $this->getGridOrm();
        $filters = $this->BRequest->request('filters');
        if ($filters) {
            $this->processGridFilters($config, $this->BUtil->fromJson($filters), $orm);
        }
        $data = $orm->paginate();
        $result = [
            'rows' => BDb::many_as_array($data['rows']),
            'state' => $data['state'],
        ];
        $this->respond($result);
    }

    public function normalizeGridConfig($config)
    {
        $colsByName = [];
        foreach ($config['columns'] as $i => &$col) {
            if (!isset($col['sortable'])) {
                $col['sortable'] = true;
            }
            if (!empty($col['type'])) {
                switch ($col['type']) {
                    case 'row-select':
                        if (empty($col['header_cell_vm'])) {
                            $col['header_cell_vm'] = 'GridHeaderCell_RowSelect';
                        }
                        if (empty($col['data_cell_vm'])) {
                            $col['data_cell_vm'] = 'GridDataCell_RowSelect';
                        }
                        if (empty($col['field'])) {
                            $col['field'] = 'row-select';
                        }
                        if (empty($col['label'])) {
                            $col['label'] = 'Selection';
                        }
                        if (empty($col['id_field'])) {
                            $col['id_field'] = 'id';
                        }
                        break;

                    case 'actions':
                        if (empty($col['header_cell_vm'])) {
                            //$col['header_cell_vm'] = 'GridHeaderCell_Actions';
                        }
                        if (empty($col['data_cell_vm'])) {
                            $col['data_cell_vm'] = 'GridDataCell_Actions';
                        }
                        if (empty($col['field'])) {
                            $col['field'] = 'actions';
                        }
                        if (empty($col['label'])) {
                            $col['label'] = 'Actions';
                        }
                        if (empty($col['actions'])) {
                            $col['actions'] = [
                                ['type' => 'edit', 'link' => $config['edit_link']],
                                ['type' => 'delete', 'link' => $config['delete_link']],
                            ];
                        }
                        foreach ($col['actions'] as $j => $a) {
                            if (empty($a['icon_class'])) {
                                switch ($a['type']) {
                                    case 'edit': $col['actions'][$j]['icon_class'] = 'fa fa-pencil'; break;
                                    case 'delete': $col['actions'][$j]['icon_class'] = 'fa fa-trash'; break;
                                }
                            }
                        }
                        $col['sortable'] = false;
                        break;
                }
            }
            $colsByName[$col['field']] = $col;
        }
        unset($col);

        if (!empty($config['filters'])) {
            foreach ($config['filters'] as &$flt) {
                if (empty($flt['field'])) {
                    if (!empty($flt['name'])) {
                        $flt['field'] = $flt['name'];
                    }
                }
                $col = !empty($colsByName[$flt['field']]) ? $colsByName[$flt['field']] : [];

                if (empty($flt['options'])) {
                    if (!empty($col['options'])) {
                        $flt['options'] = $col['options'];
                    }
                }
                if (empty($flt['type'])) {
                    if (!empty($col['type'])) {
                        $flt['type'] = $col['type'];
                    } else {
                        $flt['type'] = !empty($flt['options']) ? 'select' : 'text';
                    }
                }
                if (empty($flt['label'])) {
                    if (!empty($col['label'])) {
                        $flt['label'] = $col['label'];
                    }
                }
                if (empty($flt['ops'])) {
                    if (!empty($this->_filterOpsByType[$flt['type']])) {
                        $flt['ops'] = $this->_filterOpsByType[$flt['type']];
                    } else {
                        $flt['ops'] = $this->_filterOpsByType['text'];
                    }
                }
                if (empty($flt['default_op'])) {
                    if (!empty($this->_filterDefaultOpByType[$flt['type']])) {
                        $flt['default_op'] = $this->_filterDefaultOpByType[$flt['type']];
                    } else {
                        $flt['default_op'] = 'equals';
                    }
                }
            }
            unset($flt);
        }

        if (empty($config['pagesize_options'])) {
            $config['pagesize_options'] = [5, 10, 20, 50, 100];
        }
        return $config;
    }

    /**
     * @param array $config
     * @param array $filters
     * @param BORM $orm
     */
    public function processGridFilters(&$config, $filters, $orm)
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
                || (empty($f['val']) && $f['val'] !== 0 && $f['val'] !== '0')
                || empty($configFilterFields[$fId])
            ) {
                continue;
            }

            $stop = false;
            if (!empty($f['callback'])) {
                $gridId = $config['id'];
                $stop = $this->{$gridId}->{$f['callback']}($f, $filters[$fId]['val'], $orm);
            }
            if (!$stop) {
                $this->_defaultFilterCallback($f, $orm);
            }
        }
    }

    protected function _defaultFilterCallback($f, $orm)
    {
        switch ($f['type']) {
            case 'text':
                $val = $f;
                if (!empty($f)) {
                    $val = $f['val'];
                    $op = false;
                    switch ($f['op']) {
                        case 'equals'://equal to
                            $op = 'like';
                            break;
                        case 'starts'://start with
                            $val = $val . '%';
                            $op = 'like';
                            break;
                        case 'ends'://end with
                            $val = '%' . $val;
                            $op = 'like';
                            break;
                        case 'contains'://contain
                            $val = '%' . $val . '%';
                            $op = 'like';
                            break;
                        case 'not_contains'://does not contain
                            $val = '%' . $val . '%';
                            $op = 'not_like';
                            break;
                        case 'empty':
                            $op = 'null';
                            break;
                    }
                    if ($op) {
                        $this->_processGridFiltersOne($f, $op, $val, $orm);
                    }
                }
                break;

            case 'date':
            case 'number':
                $val = !empty($f['val']) ? $f['val'] : null;
                $from = $f['from'];
                $to = $f['to'];
                if (!empty($f)) {
                    switch ($f['op']) {
                        case 'between':
                            $this->_processGridFiltersOne($f, 'gte', $from, $orm);
                            if (isset($temp[1])) {
                                $this->_processGridFiltersOne($f, 'lte', $to, $orm);
                            }
                            break;

                        case 'from':
                            $this->_processGridFiltersOne($f, 'gte', $val, $orm);
                            break;

                        case 'to':
                            $this->_processGridFiltersOne($f, 'lte', $val, $orm);
                            break;

                        case 'equal':
                            if ($f['type'] === 'date')
                                $this->_processGridFiltersOne($f, 'like', $val . '%', $orm);
                            else
                                $this->_processGridFiltersOne($f, 'equal', $val, $orm);
                            break;

                        case 'not_in':
                            // $f['field'] has been sanitized before
                            $orm->where_raw($f['field'] . ' NOT BETWEEN ? and ?', [$from, $to]);
                            break;

                        case 'empty':
                            $this->_processGridFiltersOne($f, 'null', null, $orm);
                            break;
                    }
                }
                break;

            case 'select':
                $val = $f['val'];
                switch ($f['op']) {
                    case 'in':
                        $this->_processGridFiltersOne($f, 'in', $val, $orm);
                        break;

                    case 'not_in':
                        $this->_processGridFiltersOne($f, 'not_in', $val, $orm);
                        break;

                    case 'empty':
                        $this->_processGridFiltersOne($f, 'null', null, $orm);
                        break;
                }
                break;
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

}