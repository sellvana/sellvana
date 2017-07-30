<?php

/**
 * Class FCom_Admin_Controller_Abstract_Report
 *
 * @property FCom_Core_Model_Field $FCom_Core_Model_Field
 */
abstract class FCom_Admin_Controller_Abstract_Report extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_periodTypes = [
        'day' => (('Day')),
        'week' => (('Week')),
        'month' => (('Month')),
        'quarter' => (('Quarter')),
        'year' => (('Year'))
    ];

    protected $_systemFields = [];
    protected $_visibleFields = [];
    protected $_selectModels = [];

    /**
     * @return BView|FCom_Core_View_BackboneGrid
     */
    public function gridView()
    {
        $view = parent::gridView();
        $grid = $view->get('grid');
        $config = $grid['config'];

        $labels = $this->_getFieldLabels();
        $this->BEvents->fire(static::$_origClass . '::fieldLabels', ['data' => &$labels]);

        $this->_selectAllFields($config['orm']);
        foreach ($config['columns'] as &$column) {
            $column['label'] = $column['name'];
            if (!empty($column['name']) && !empty($labels[$column['name']])) {
                $column['label'] = $labels[$column['name']];
            }
        }

        $view->set('grid', ['config' => $config]);
        return $view;
    }

    /**
     * return config to build grid
     * @return array
     */
    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array_merge($config['columns'], $this->_addAllColumns());
        return $config;
    }


    /**
     * Get field labels
     *
     * @return array
     */
    protected function _getFieldLabels()
    {
        return [];
    }

    /**
     * Get cell formats (currency, date, datetime, etc.)
     *
     * @return array
     */
    protected function _getCellFormats()
    {
        return [];
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $view = $args['page_view'];
        $view->set('actions', []);
    }

    /**
     * @param array $filter
     * @param string $val
     * @param BORM $orm
     *
     * @return bool
     */
    public function periodTypeCallback($filter, $val, $orm)
    {
        $field = 'o.create_at';
        switch ($val) {
            case 'year':
                $expr = "YEAR({$field})";
                break;
            case 'quarter':
                $expr = "CONCAT(YEAR({$field}), '-', QUARTER({$field}))";
                break;
            case 'month':
                $expr = "DATE_FORMAT({$field}, '%Y-%m')";
                break;
            case 'week':
                $expr = "DATE_FORMAT({$field}, '%Y-%u')";
                break;
            case 'day':
            default:
                $expr = "DATE_FORMAT({$field}, '%Y-%m-%d')";
                break;
        }

        $orm->group_by_expr($expr);
        $orm->select_expr($expr, 'period');
        $orm->select_expr("'" . $val . "'", $filter['field']);

        return true;
    }

    /**
     * Get filters from either request, or personalization
     *
     * @return array
     */
    protected function _getFilters()
    {
        $request = $this->BRequest->request();
        if (!empty($request['hash'])) {
            $request = (array)$this->BUtil->fromJson(base64_decode($request['hash']));
        } elseif (!empty($request['filters'])) {
            $request['filters'] = $this->BUtil->fromJson($request['filters']);
        }

        /** @var FCom_Core_View_BackboneGrid $view */
        if (!empty($request['filters'])) {
            return $request['filters'];
        } else {
            $pers = $this->FCom_Admin_Model_User->personalize();
            $gridId = $this->origClass();
            $persState = !empty($pers['grid'][$gridId]['state']) ? $pers['grid'][$gridId]['state'] : [];
            if (!empty($persState['filters'])) {
                return $persState['filters'];
            }
        }

        return [];
    }

    /**
     * @return array
     */
    protected function _addAllColumns()
    {
        $columns = [];
        $cellFormats = $this->_getCellFormats();
        /** @var FCom_Core_Model_Abstract $model */
        foreach ($this->_selectModels as $alias => $model) {
            $table = $model->table();
            $fields = BDb::ddlFieldInfo($table);
            foreach ($fields as $field) {
                $fieldId = $field->orm->get('Field');
                $fieldName = $alias . '_' . $fieldId;
                if (!in_array($fieldName, $this->_systemFields)) {
                    $columns[] = [
                        'name' => $fieldName,
                        'index' => $alias . '.' . $fieldId,
                        'hidden' => (!in_array($fieldName, $this->_visibleFields)),
                        'cell' => !empty($cellFormats[$fieldName]) ? $cellFormats[$fieldName] : ''
                    ];
                }
            }
        }

        return $columns;
    }

    /**
     * @param BORM $orm
     */
    protected function _selectAllFields($orm)
    {
        /** @var FCom_Core_Model_Abstract $model */
        foreach ($this->_selectModels as $alias => $model) {
            $table = $model->table();
            $fields = BDb::ddlFieldInfo($table);
            foreach ($fields as $field) {
                $orm->select($alias . '.' . $field->orm->get('Field'), $alias . '_' . $field->orm->get('Field'));
            }
        }
    }

    protected function _addProductCustomFields($config)
    {
        $fields = $this->FCom_Core_Model_Field->orm('f')->where('field_type', 'product')->find_many();
        foreach ($fields as $field) {
            $type = 'text';
            if (substr($field->get('table_field_type'), 0, 3) == 'int') {
                $type = 'number-range';
            }
            $config['columns'][] = ['name' => $field->get('field_code'), 'index' => $field->get('field_code'), 'hidden' => true];
            $config['filters'][] = ['field' => $field->get('field_code'), 'type' => $type, 'hidden' => true];
        }

        return $config;
    }

    protected function _getProductCustomFieldLabels()
    {
        $labels = [];
        $fields = $this->FCom_Core_Model_Field->orm('f')->where('field_type', 'product')->find_many();
        foreach ($fields as $field) {
            $labels[$field->get('field_code')] = $field->get('field_name');
        }

        return $labels;
    }
}