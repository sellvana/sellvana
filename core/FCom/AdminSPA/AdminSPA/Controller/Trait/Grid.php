<?php

/**
 * Trait FCom_AdminSPA_AdminSPA_Controller_Trait_Grid
 *
 * @property FCom_Admin_Model_User FCom_Admin_Model_User
 */
trait FCom_AdminSPA_AdminSPA_Controller_Trait_Grid
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
            'not_between' => 'not between',
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

    static protected $_defaultGridActionConfig = [
        //'mobile_group' => 'actions',
    ];

    /**
     * @return array
     */
    abstract public function getGridConfig();

    public function getGridOrm()
    {
        $modelClass = static::$_modelClass;
        return $this->{$modelClass}->orm();
    }

    public function getProcessedGridConfig()
    {
        $config = $this->getGridConfig();
        $config = $this->normalizeGridConfig($config);
        $config = $this->applyGridPersonalization($config);
        return $config;
    }

    public function action_grid_config()
    {
        $config = $this->getProcessedGridConfig();

        if (!empty($config['state']['filters'])) {
            foreach ($config['state']['filters'] as &$f) {
                if (is_array($f['val'])) {
                    $f['values'] = $f['val'];
                } else {
                    $f['value'] = $f['val'];
                }
                unset($f['val']);
            }
            unset($f);
        }

        $this->respond($config);
    }

    public function action_grid_data()
    {
        $config = $this->getProcessedGridConfig();
        $config = $this->processGridStatePersonalization($config);
        $filters = isset($config['state']['filters']) ? $config['state']['filters'] : null;
        $data = $this->getGridRequestOrm()->paginate($config['state']);
        $data = $this->processGridPageData($data);

        if ($filters) {
            foreach ($filters as &$f) {
                if (is_array($f['val'])) {
                    $f['values'] = $f['val'];
                } else {
                    $f['value'] = $f['val'];
                }
                unset($f['val']);
            }
            unset($f);
        }

        $data['state']['filters'] = $filters;
        $result = [
            'rows' => $data['rows'],
            'state' => $data['state'],
        ];
        $this->respond($result);
    }

    public function action_grid_data__POST()
    {
        try {
            $result = [];
            $modelClass = static::$_modelClass;
            $modelName = static::$_modelName;
            $post = $this->BRequest->post();
            if (empty($post['do']) || empty($post['ids'])) {
                throw new BException('Invalid request');
            }
            $orm = $this->{$modelClass}->orm('_main')->where_in('_main.id', $post['ids']);
            switch ($post['do']) {
                case 'bulk_update':
                    if (empty($post['data'])) {
                        throw new BException('Invalid request: missing data');
                    }
                    if (!empty($post['data'][$modelName])) {
                        $data = $this->getBulkUpdateData('edit_' . $modelName . 's', $modelName, $post);
                        $orm->iterate(function ($r) use ($data) { $r->set($data)->save(); });
                    }
                    $this->addMessage('Rows have been updated successfully.', 'success');
                    break;

                case 'bulk_delete':
                    $orm->iterate(function ($r) { $r->delete(); });
                    $this->addMessage('Rows have been deleted successfully.', 'success');
                    break;
            }
            $this->ok();
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond(['result' => $result]);
    }

    public function action_grid_personalize__POST()
    {
        try {
            $r = $this->BRequest->request();
            $data = [];
            if (empty($r['do'])) {
                $this->BResponse->json(['error' => true, 'r' => $r]);
                return;
            }
            switch ($r['do']) {
                case 'grid.col.hidden':
                    if (empty($r['grid']) || empty($r['col']) || !isset($r['hidden'])) {
                        break;
                    }
                    $columns = [$r['col'] => ['hidden' => !empty($r['hidden']) && $r['hidden'] !== 'false']];
                    $data = ['grid' => [$r['grid'] => ['columns' => $columns]]];

                    break;

                case 'grid.cols.order':
                    if (is_array($r['cols'])) {
                        $cols = $r['cols'];
                    } else {
                        $cols = $this->BUtil->fromJson($r['cols']);
                    }

                    $columns = [];
                    foreach ($cols as $i => $col) {
                        if (empty($col['name'])) {
                            continue;
                        }
                        $columns[$col['name']] = ['position' => $col['position']];
                    }
                    $data = ['grid' => [$r['grid'] => ['columns' => $columns]]];

                    break;
            }
            $this->BEvents->fire(__METHOD__, ['request' => $r, 'data' => &$data]);
            $this->FCom_Admin_Model_User->personalize($data);
            $this->ok()->respond();
        } catch (Exception $e) {
            $this->addResponse($e)->respond();
        }
    }

    public function action_grid_export()
    {
        $orm = $this->getGridRequestOrm();
        $type = $this->BRequest->request('type') ?: 'csv';
        switch ($type) {
            case 'csv':
                $this->exportCsv($orm);
                die;
                break;

            default:
                throw new BException('Invalid export type');
        }
    }

    public function getGridRequestOrm()
    {
        $config = $this->getGridConfig();
        $config = $this->normalizeGridConfig($config);
        $orm = $this->getGridOrm();
        $filters = !empty($config['state']['filters']) ? $config['state']['filters'] : $this->BRequest->request('filters');
        if ($filters) {
            if (is_string($filters)) {
                $filters = $this->BUtil->fromJson($filters);
            }
            $this->processGridFilters($config, $filters, $orm);
        }
        return $orm;
    }

    public function processGridPageData($data)
    {
        $data['rows'] = BDb::many_as_array($data['rows']);
        return $data;
    }


    public function normalizeGridConfig($config)
    {
        if (method_exists($this, 'getGridOrm')) {
            $indexPrefix = $this->getGridOrm()->table_alias() . '.';
        }

        if (!empty($config['data_url']) && empty($config['personalize_url'])) {
            $config['personalize_url'] = str_replace('grid_data', 'grid_personalize', $config['data_url']);
        }

        $colsByName = [];
        foreach ($config['columns'] as $i => &$col) {
            if (!isset($col['sortable'])) {
                $col['sortable'] = true;
            }
            /** @deprecated TODO: one convention */
            if (empty($col['field']) && !empty($col['name'])) {
                $col['field'] = $col['name'];
            } elseif (!empty($col['field']) && empty($col['name'])) {
                $col['name'] = $col['field'];
            } elseif (!empty($col['type'])) {
                $col['name'] = $col['field'] = $col['type'];
            } else {
                throw new BException('Invalid field configuration: ' . print_r($col, 1));
            }
            if (empty($col['index']) && !empty($indexPrefix) && !empty($col['name'])) {
                $col['index'] = $indexPrefix . $col['name'];
            }
            if (!empty($col['type'])) {
                switch ($col['type']) {
                    case 'row-select':
                    case 'row_select':
                        if (empty($col['header_component'])) {
                            $col['header_component'] = 'sv-comp-grid-header-cell-row-select';
                        }
                        if (empty($col['datacell_component'])) {
                            $col['datacell_component'] = 'sv-comp-grid-data-cell-row-select';
                        }
                        if (empty($col['name'])) {
                            $col['name'] = 'row-select';
                        }
                        if (empty($col['label'])) {
                            $col['label'] = 'Selection';
                        }
                        if (empty($col['id_field'])) {
                            $col['id_field'] = 'id';
                        }
                        break;

                    case 'actions':
                    case 'btn_group':
                        if (empty($col['header_component'])) {
                            //$col['header_component'] = 'sv-comp-grid-header-cell-actions';
                        }
                        if (empty($col['datacell_component'])) {
                            $col['datacell_component'] = 'sv-comp-grid-data-cell-actions';
                        }
                        if (empty($col['name'])) {
                            $col['name'] = 'actions';
                        }
                        if (empty($col['label'])) {
                            $col['label'] = 'Actions';
                        }
                        if (empty($col['actions'])) {
                            if (!empty($config['edit_link'])) {
                                $col['actions'][] = ['type' => 'edit', 'link' => $config['edit_link']];
                            }
                            if (!empty($config['delete_link'])) {
                                $col['actions'][] = ['type' => 'delete', 'link' => $config['delete_link']];
                            }
                        }
                        if (!empty($col['actions'])) {
                            foreach ($col['actions'] as $j => $a) {
                                if (empty($a['icon_class']) && !empty($a['type'])) {
                                    switch ($a['type']) {
                                        case 'edit':
                                            $col['actions'][$j]['icon_class'] = 'fa fa-pencil';
                                            break;
                                        case 'delete':
                                            $col['actions'][$j]['icon_class'] = 'fa fa-trash';
                                            break;
                                    }
                                }
                            }
                        }
                        $col['sortable'] = false;
                        break;
                }
            }
            $colsByName[$col['name']] = $col;
        }
        unset($col);

        if (!empty($config['filters'])) {
            if ($config['filters'] === true) {
                $config['filters'] = [];
                foreach ($config['columns'] as $col) {
                    if (!empty($col['type']) && in_array($col['type'], ['row-select', 'actions'])) {
                        continue;
                    }
                    $config['filters'][] = ['name' => $col['name']];
                }
            }
            foreach ($config['filters'] as &$flt) {
                /** @deprecated TODO: one convention */
                if (empty($flt['field']) && !empty($flt['name'])) {
                    $flt['field'] = $flt['name'];
                } elseif (!empty($flt['field']) && empty($flt['name'])) {
                    $flt['name'] = $flt['field'];
                }
                $col = !empty($colsByName[$flt['name']]) ? $colsByName[$flt['name']] : [];


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
                if (empty($flt['index']) && !empty($col)) {
                    $flt['index'] = !empty($col['index']) ? $col['index'] : $col['field'];
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

        if (!empty($config['page_actions'])) {
            $actionGroups = [];
            if (!empty($config['page_actions']['default'])) {
                $def = array_merge(static::$_defaultGridActionConfig, $config['page_actions']['default']);
                unset($config['page_actions']['default']);
            } else {
                $def = static::$_defaultGridActionConfig;
            }
            foreach ($config['page_actions'] as &$act) {
                $act = array_merge($def, $act);
                foreach (['desktop_group', 'mobile_group'] as $g) {
                    $group = !empty($act[$g]) ? $act[$g] : (!empty($act['group']) ? $act['group'] : null);
                    if (!empty($group)) {
                        if (empty($actionGroups[$g][$group])) {
                            $actionGroups[$g][$group] = $act;
                        } else {
                            $actionGroups[$g][$group]['children'][] = $act;
                        }
                    }
                }
            }
            unset($act);
            if (!empty($actionGroups['desktop_group'])) {
                $config['page_actions_groups']['desktop'] = array_values($actionGroups['desktop_group']);
            }
            if (!empty($actionGroups['mobile_group'])) {
                $config['page_actions_groups']['mobile'] = array_values($actionGroups['mobile_group']);
            }
        }

        if (!empty($config['pager']) && $config['pager'] === true) {
            $config['pager'] = [
                'pagesize_options' => [5, 10, 20, 50, 100],
            ];
        }

        if (!empty($config['export']) && $config['export'] === true) {
            $config['export'] = [
                'url' => 'orders/grid_export',
                'format_options' => [
                    ['value' => 'csv', 'label' => 'CSV'],
                ],
            ];
        }

        if (empty($config['state'])) {
            $config['state'] = [
                'ps' => 10,
            ];
        }

        return $config;
    }

    public function processStaticGridData($data, $request = null)
    {
        if (null === $request) {
            $request = $this->BRequest->get();
        }
        $data['state'] = $this->BUtil->arrayMerge($data['state'], $request);

        // [] TODO: implement filters and pages
//        foreach ($data['rows'] as $i => $r) {
//            $show = true;
//            if (!$show) {
//                unset($data['rows'][$i]);
//            }
//        }

        if (!empty($data['state']['s'])) {
            $s = $data['state']['s'];
            $sd = !empty($data['state']['sd']) ? $data['state']['sd'] : 'asc';
            usort($data['rows'], function ($r1, $r2) use ($s, $sd) {
                $d1 = !empty($r1[$s]) ? $r1[$s] : '';
                $d2 = !empty($r2[$s]) ? $r2[$s] : '';
                switch ($sd) {
                    case 'asc': return $d1 < $d2 ? -1 : ($d1 > $d2 ? 1 : 0);
                    case 'desc': return $d1 < $d2 ? 1 : ($d1 > $d2 ? -1 : 0);
                }
            });
        }

        return $data;
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
            $indexes = $this->BUtil->arraySeqToMap($config['filters'], 'field', 'index');
            $types = $this->BUtil->arraySeqToMap($config['filters'], 'field', 'type');

            foreach ($filters as $fId => &$f) {
                if (is_array($f)) {
                    $f['field'] = !empty($f['field']) ? $f['field'] : $fId;
                    if (!empty($indexes[$f['field']])) {
                        $f['field'] = $indexes[$f['field']];
                    }
                    if (!preg_match('#^[A-Za-z0-9_.]+$#', $f['field'])) {
                        unset($filters[$fId]);
                        continue;
                    }
                    if (empty($f['type'])) {
                        $f['type'] = !empty($types[$f['field']]) ? $types[$f['field']] : 'text';
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
                    $configFilterFields[$f['field']] = $fId;
                    $configFilterFields[$indexes[$f['field']]] = $fId;
                }
            }
        }
        foreach ($filters as $fId => $f) {
            if ($fId === '_quick'
                || !is_array($f)
                || empty($f['field'])
                || empty($f['type'])
                || (!isset($configFilterFields[$f['field']]) && !in_array($f['field'], $indexes))
                || ((!isset($f['val']) || $f['val'] === '') && (!isset($f['from']) || $f['from'] === '') && (!isset($f['to']) || $f['to'] === ''))
            ) {
                continue;
            }

            $stop = false;
            $fieldConfig = $config['filters'][$configFilterFields[$f['field']]];
            if (!empty($fieldConfig['callback'])) {
                $gridId = $config['id'];
                $stop = $this->{$gridId}->{$fieldConfig['callback']}($fieldConfig, $filters[$fId]['val'], $orm);
            }
            if (!$stop) {
                $this->_defaultFilterCallback($fieldConfig, $f, $orm);
            }
        }
    }

    protected function _defaultFilterCallback($fieldConfig, $f, $orm)
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
                            if ($to) {
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

    public function exportCsv($orm)
    {
        $config = $this->getGridConfig();
        $config = $this->normalizeGridConfig($config);
        $config = $this->applyGridPersonalization($config);

        $dir = $this->BApp->storageRandomDir() . '/export';
        $this->BUtil->ensureDir($dir);
        $filename = $dir . '/' . $config['id'] . '.csv';
        $fp = fopen($filename, 'w');
        fwrite($fp, "\xEF\xBB\xBF"); // add UTF8 BOM character to open excel.

        $headers = $this->buildExportHeaders($config);
        fputcsv($fp, $headers);

        $orm->iterate(function ($row) use ($config, $fp) {
            $data = $this->buildExportDataRow($row, $config);
            fputcsv($fp, $data);
        });

        fclose($fp);
        $this->BResponse->sendFile($filename);
    }

    public function callbackSkipColumn($col)
    {
        if (empty($col['field'])) {
            return true;
        }
        if (!empty($col['hidden']) && $col['hidden'] !== 'false') {
            return true;
        }
        if (!empty($col['cell']) || (!empty($col['type']) && $col['type'] === 'thumb')) {
            return true;
        }
        if (!empty($col['type']) && in_array($col['type'], ['actions', 'row-select'])) {
            return true;
        }
        return false;
    }

    public function buildExportHeaders($config)
    {
        $headers = [];
        foreach ($config['columns'] as $i => $col) {
            if ($this->callbackSkipColumn($col)) {
                continue;
            }
            $headers[] = $col['field'];
        }
        return $headers;
    }

    public function buildExportDataRow($row, $config)
    {
        $data = [];
        foreach ($config['columns'] as $col) {
            if ($this->callbackSkipColumn($col)) {
                continue;
            }
            $k = $col['field'];
            $val = $row->get($k);
            if ($val === null) {
                $val = '';
            }
            if (isset($col['options'][$val])) {
                $val = $col['options'][$val];
            }
            $data[] = $val;
        }
        return $data;
    }

    public function applyGridPersonalization($config)
    {
        $pers = $this->FCom_Admin_Model_User->personalize();
        if (empty($pers['grid'][$config['id']])) {
            return $config;
        }
        $p = $pers['grid'][$config['id']];
        if (!empty($p['state'])) {
            $config['state'] = $p['state'];
        }
        if (!empty($p['columns'])) {
            foreach ($config['columns'] as &$col) {
                if (isset($p['columns'][$col['name']]['hidden'])) {
                    $col['hidden'] = $p['columns'][$col['name']]['hidden'];
                }
                if (isset($p['columns'][$col['name']]['position'])) {
                    $col['position'] = $p['columns'][$col['name']]['position'];
                }
            }
            unset($col);
            usort($config['columns'], function ($c1, $c2) {
                $d1 = !empty($c1['position']) ? $c1['position'] : 999;
                $d2 = !empty($c2['position']) ? $c2['position'] : 999;
                return $d1 < $d2 ? -1 : ($d1 > $d2 ? 1 : 0);
            });
        }
        $config = $this->_addFilterConfigToState($config);
        return $config;
    }

    public function processGridStatePersonalization($config, $state = null)
    {
        if (null === $state) {
            $state = $this->BRequest->request();
        }
        if (isset($state['filters']) && is_string($state['filters'])) {
            $state['filters'] = $this->BUtil->fromJson($state['filters']);
        }
        $this->FCom_Admin_Model_User->personalize(["grid/{$config['id']}/state" => $state], true);
        $config['state'] = $state;
        $config = $this->_addFilterConfigToState($config);
        return $config;
    }

    protected function _addFilterConfigToState($config)
    {
        if (!empty($config['state']['filters']) && !empty($config['filters'])) {
            foreach ($config['state']['filters'] as &$f) {
                foreach ($config['filters'] as $flt) {
                    if ($flt['field'] === $f['field']) {
                        $f['config'] = $flt;
                        break;
                    }
                }
            }
            unset($f);
        }
        return $config;
    }

    public function getAllowedFieldsForBulkUpdate($bulkAction = null, $model = null)
    {
        $gridConfig = $this->getProcessedGridConfig();
        $fields = [];
        foreach ($gridConfig['bulk_actions'] as $action) {
            if (null !== $bulkAction && $action['name'] !== $bulkAction) {
                continue;
            }
            foreach ($action['popup']['form']['config']['fields'] as $field) {
                if (null === $bulkAction) {
                    $fields[$action['name']][$field['model']] = $field['name'];
                } elseif (null === $model) {
                    $fields[$field['model']][] = $field['name'];
                } elseif ($field['model'] === $model) {
                    $fields[] = $field['name'];
                }
            }
        }
        return $fields;
    }

    public function getBulkUpdateData($bulkAction, $model, $post = null)
    {
        if ($post === null) {
            $post = $this->BRequest->post();
        }
        $allowedFields = $this->getAllowedFieldsForBulkUpdate($bulkAction, $model);
        $data = [];
        foreach ($post['data'][$model] as $k => $v) {
            if (!preg_match('/^[a-z0-9_]+$/', $k)) {
                continue;
            }
            if (!in_array($k, $allowedFields)) {
                continue;
            }
            if ($v === 'true') {
                $v = 1;
            } elseif ($v === 'false') {
                $v = 0;
            } elseif ($v === 'null') {
                $v = null;
            }
            $data[$k] = $v;
        }
        return $data;
    }
}