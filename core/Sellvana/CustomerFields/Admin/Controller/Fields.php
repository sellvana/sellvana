<?php

/**
 * Class Sellvana_CustomerFields_Admin_Controller_Fields
 *
 * @property FCom_Core_Model_Field FCom_Core_Model_Field
 * @property FCom_Core_Model_FieldOption FCom_Core_Model_FieldOption
 */
class Sellvana_CustomerFields_Admin_Controller_Fields extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'customer_fields';

    public function fieldsGridConfig()
    {
        $fld = $this->FCom_Core_Model_Field;
        $subSql = '(select count(*) from ' . $this->FCom_Core_Model_FieldOption->table() . ' where field_id=f.id)';
        $orm = $fld->orm('f')->where('field_type', 'customer')
            ->select('f.*')->select($subSql, 'num_options');

        $config = [
            'config' => [
                'id' => 'fields',
                'caption' => 'Fields',
                'orm' => $orm,
                'data_url' => $this->BApp->href('customerfields/fields/field_grid_data'),
                'edit_url' => $this->BApp->href('customerfields/fields/field_grid_data'),
                'columns' => [
                    ['type' => 'row_select'],
                    ['type' => 'btn_group', 'buttons' => [
                        [
                            'name' => 'custom',
                            'icon' => 'icon-edit-sign',
                            'cssClass' => 'btn-custom',
                            'callback' => 'showModalToEditFields'
                        ],
                        //['name' => 'edit'],
                        ['name' => 'delete']
                    ]],
                    ['name' => 'id', 'label' => 'ID', 'width' => 30, 'hidden' => true],
                    ['type' => 'input', 'name' => 'field_code', 'label' => 'Field Code', 'width' => 100, 'editable' => true, 'editor' => 'text',
                            'default' => '', 'addable' => true, 'multirow_edit' => true, 'validation' => ['required' => true,
                            'unique' => $this->BApp->href('/customerfields/fields/unique_field')]],
                    ['type' => 'input', 'name' => 'field_name', 'label' => 'Field Name', 'width' => 100, 'editable' => true, 'editor' => 'text',
                            'default' => '', 'addable' => true, 'multirow_edit' => true, 'validation' => ['required' => true]],
                    ['type' => 'input', 'name' => 'frontend_label', 'label' => 'Frontend Label', 'width' => 100, 'editable' => true, 'editor' => 'text',
                            'default' => '', 'addable' => true, 'multirow_edit' => true, 'validation' => ['required' => true]],
                    ['type' => 'input', 'name' => 'frontend_show', 'label' => 'Show on frontend', 'width' => 90,
                            'editable' => true, 'addable' => true, 'multirow_edit' => true, 'validation' => ['required' => true],
                            'options' => $fld->fieldOptions('frontend_show'), 'editor' => 'select'],
                    ['type' => 'input', 'name' => 'account_edit', 'label' => 'Use in account edit', 'width' => 70,
                            'editable' => true, 'addable' => true, 'multirow_edit' => true, 'validation' => ['required' => true],
                            'options' => $fld->fieldOptions('account_edit'), 'editor' => 'select'],
                    ['type' => 'input', 'name' => 'register_form', 'label' => 'Use in register form', 'width' => 70,
                            'editable' => true, 'addable' => true, 'multirow_edit' => true, 'validation' => ['required' => true],
                            'options' => $fld->fieldOptions('register_form'), 'editor' => 'select'],
                    ['type' => 'input', 'name' => 'sort_order', 'label' => 'Sort order', 'width' => 30, 'editable' => true, 'editor' => 'text',
                            /*'editor'=>'select',*/ 'validate' => 'number', 'addable' => true,
                            'multirow_edit' => true, 'validation' => ['required' => true]/*,
                            'options'=>range(0,20)*/],
                            /*'facet_select'=>array('label'=>'Facet', 'width'=>200, 'editable'=>true,
                                'options'=>array('No'=>'No', 'Exclusive'=>'Exclusive', 'Inclusive'=>'Inclusive')),*/
                    ['type' => 'input', 'name' => 'table_field_type', 'label' => 'DB Type', 'width' => 180, 'editor' => 'select',
                            'addable' => true, 'editable' => true, 'validation' => ['required' => true], 'options' => $fld->fieldOptions('table_field_type')],
                    ['type' => 'input', 'name' => 'admin_input_type', 'label' => 'Input Type', 'width' => 180,
                        'editable' => true, 'editor' => 'select', 'addable' => true, 'multirow_edit' => true,
                        'validation' => ['required' => true], 'options' => $fld->fieldOptions('admin_input_type')],
                    ['type' => 'input', 'name' => 'num_options', 'label' => 'Options', 'width' => 30, 'default' => '0'],
                    ['type' => 'input', 'name' => 'system', 'label' => 'System field', 'width' => 90, 'editable' => false, 'editor' => 'select',
                         'addable' => true, 'multirow_edit' => true, 'validation' => ['required' => true], 'options' => ['0' => 'No', '1' => 'Yes']],
                    ['type' => 'input', 'name' => 'multilanguage', 'label' => 'Multi Language', 'width' => 90,
                        'editable' => true, 'editor' => 'select', 'addable' => true, 'multirow_edit' => true,
                        'validation' => ['required' => true], 'options' => ['0' => 'No', '1' => 'Yes']],
                    ['type' => 'input', 'name' => 'required', 'label' => 'Required', 'width' => 90, 'editable' => true,
                        'editor' => 'select', 'addable' => true, 'multirow_edit' => true, 'validation' => ['required' => true],
                        'options' => ['1' => 'Yes', '0' => 'No']],
                ],
                'filters' => [
                    ['field' => 'field_code', 'type' => 'text'],
                    ['field' => 'field_name', 'type' => 'text'],
                    ['field' => 'frontend_label', 'type' => 'text'],
                    ['field' => 'frontend_show', 'type' => 'multiselect'],
                    ['field' => 'table_field_type', 'type' => 'multiselect'],
                    ['field' => 'admin_input_type', 'type' => 'multiselect'],
                    ['field' => 'num_options', 'type' => 'text'],
                    ['field' => 'system', 'type' => 'multiselect'],
                    ['field' => 'multilanguage', 'type' => 'multiselect'],
                    ['field' => 'required', 'type' => 'multiselect'],
                    '_quick' => ['expr' => 'field_code like ? or id like ', 'args' => ['%?%', '%?%']]
                ],
                'actions' => [
                    'add-field' => [
                        'caption' => 'Add a field',
                        'type' => 'button',
                        'id' => 'add-field-from-grid',
                        'class' => 'btn-primary',
                        'callback' => 'showModalToAddField',
                    ],
                    'edit' => true,
                    'delete' => true
                ],
                'grid_before_create' => 'fieldsGridRegister',
                'callbacks' => [
                    'componentDidMount' => 'fieldsGridRegister'
                ]
                //'new_button' => '#add_new_field'
            ]
        ];
        return $config;
    }

    public function optionsGridConfig()
    {
        $config = [
            'config' => [
                'id' => 'options-grid',
                'caption' => 'Fields',
                'dataUrl' => $this->BApp->href('customerfields/fields/field_option_grid_data?field_id='),
                'data_url' => $this->BApp->href('customerfields/fields/field_grid_data'),
                'edit_url' => $this->BApp->href('customerfields/fields/field_grid_data'),
                'data' => [],
                'data_mode' => 'local',
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'width' => 30, 'hidden' => true],
                    ['type' => 'input', 'name' => 'label', 'label' => 'Label', 'width' => 300, 'editable' => 'inline',
                        'sortable' => false, 'validation' => ['required' => true]],
                    ['type' => 'btn_group',
                        'buttons' => [['name' => 'delete', 'noconfirm' => true]]
                    ]

                ],
                'filters' => [
                    '_quick' => ['expr' => 'field_code like ? or id like ', 'args' => ['%?%', '%?%']]
                ],
                'actions' => [
                    //'new' => ['caption' => 'Insert New Option'],
                    'add-options' => [
                        'caption' => 'Insert New Option',
                        'type' => 'button',
                        'id' => 'add_new_field',
                        'class' => 'btn-primary',
                        'callback' => 'insertNewOption',
                    ],
                    'delete' => ['caption' => 'Remove', 'confirm' => false]
                ],
                'callbacks' => [
                    'componentDidMount' => 'optionsModalGridRegister'
                ],
                'grid_before_create' => 'optionsGridRegister',
                //'after_modalForm_render' => 'optionsGridRendered'
            ]
        ];

        return $config;
    }

    public function action_fields()
    {
        $this->layout('/customerfields/fields');
    }

    public function action_grid_data()
    {
        $view = $this->view('core/backbonegrid');
        $view->set('grid', $this->fieldSetsGridConfig());
        $data = $view->generateOutputData();
        $this->BResponse->json([
            ['c' => $data['state']['c']],
            $this->BDb->many_as_array($data['rows']),
        ]);
    }

    public function action_field_grid_data()
    {
        /** @var FCom_Core_View_BackboneGrid $view */
        $view = $this->view('core/backbonegrid');
        $view->set('grid', $this->fieldsGridConfig());
        $data = $view->generateOutputData();
        $this->BResponse->json([
            ['c' => $data['state']['c']],
            $this->BDb->many_as_array($data['rows']),
        ]);
    }

    public function action_field_option_grid_data()
    {
        $orm = $this->FCom_Core_Model_FieldOption->orm('fo')->select('fo.*')
            ->where('field_id', $this->BRequest->get('field_id'));
        $data = $this->view('core/backbonegrid')->processORM($orm, __METHOD__);
        $this->BResponse->json([
            ['c' => $data['state']['c']],
            $this->BDb->many_as_array($data['rows']),
        ]);
    }

    public function action_options()
    {
        $id = $this->BRequest->get('id');
        $options = $this->FCom_Core_Model_FieldOption->getListAssocById($id);

        $this->BResponse->json(
            [
                'success' => true,
                'options' => $options
            ]
        );
    }

    public function action_field_grid_data__POST()
    {
        //$this->BResponse->json(['success' => true, 'options' => $op]);
        $this->_processGridDataPost('FCom_Core_Model_Field');
    }

    public function gridPostAfter($args)
    {
        if ($this->getAction() == 'field_grid_data') {
            /** @var FCom_Core_Model_Field $model */
            $data = $args['data'];
            $model = $args['model'];
            $hlp = $this->FCom_Core_Model_FieldOption;
            $op = 0;

            // save options in case field is dropdown
            if (!empty($data['admin_input_type']) && in_array($data['admin_input_type'], ['select', 'multiselect']) && !empty($data['rows'])) {
                $models = $hlp->orm()->where_in('id', $this->BUtil->arrayToOptions($data['rows'], 'id'))->find_many_assoc();

                $rowDeleteIds = !empty($data['rowsDelete']) ? $data['rowsDelete'] : [];

                foreach ($data['rows'] as $row) {
                    if (!in_array($row['id'], $rowDeleteIds)) { //make sure this row is not in rows will be deleted
                        if (!empty($models[$row['id']])) { //update option
                            $models[$row['id']]->set('label', $row['label'])->save();
                            $op++;
                        } else { //create option
                            $rowData = ['field_id' => $model->id, 'label' => (string)$row['label']];
                            if (!$hlp->orm()->where($rowData)->find_one()) {
                                $hlp->create($rowData)->save();
                                $op++;
                            }
                        }
                    }
                }

                if ($rowDeleteIds) {
                    $hlp->delete_many(['id' => $rowDeleteIds]);
                }

            } else {
                // Delete all options
                $hlp->delete_many(['field_id' => $model->id]);
            }

            $args['result']['num_options'] = $op;
        }
    }

    public function action_field_option_grid_data__POST()
    {
        $p = $this->BRequest->post();
        $hlp = $this->FCom_Core_Model_FieldOption;
        $op = 0;

        $models = $hlp->orm()->where_in('id', $this->BUtil->arrayToOptions($p['rows'], 'id'))->find_many_assoc();
        foreach ($p['rows'] as $row) {
            if (!empty($models[$row['id']])) {
                $models[$row['id']]->set('label', $row['label'])->save();
                $op++;
            } else {
                $data = ['field_id' => (int)$p['field_id'], 'label' => (string)$row['label']];
                if (!$hlp->orm()->where($data)->find_one()) {
                    $hlp->create($data)->save();
                    $op++;
                }

            }
        }
        $this->BResponse->json(['success' => true, 'options' => $op]);
        //$this->_processGridDataPost('Sellvana_CustomerFields_Model_FieldOption', array('field_id'=>$this->BRequest->get('field_id')));
    }

    public function action_unique_field__POST()
    {
        $r = $this->BRequest;
        $p = $r->post();
        try {
            if (empty($p['_name'])) {
                throw new BException('Invalid field name');
            }
            $name = $this->BDb->sanitizeFieldName($p['_name']);
            if (empty($p[$name])) {
                throw new BException('Invalid field value');
            }
            $val = $p[$name];
            $exists = $this->FCom_Core_Model_Field->orm()->where('field_type', 'customer')
                ->where($name, $val)->find_one();
            $result = ['unique' => !$exists, 'id' => !$exists ? -1 : $exists->id()];
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage()];
        }
        $this->BResponse->json($result);
    }
}
