<?php

/**
 * Class Sellvana_CatalogFields_Admin_Controller_FieldSets
 * @property FCom_Core_Model_Fieldset $FCom_Core_Model_Fieldset
 * @property FCom_Core_Model_FieldsetField $FCom_Core_Model_FieldsetField
 * @property FCom_Core_Model_Field $FCom_Core_Model_Field
 * @property FCom_Core_Model_FieldOption $FCom_Core_Model_FieldOption
 */
class Sellvana_CatalogFields_Admin_Controller_FieldSets extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'catalog_fields';

    public function fieldSetsGridConfig()
    {
        $orm = $this->FCom_Core_Model_Fieldset->orm('s')->select('s.*')
            ->select('(select count(*) from ' . $this->FCom_Core_Model_FieldsetField->table() . ' where set_id=s.id)', 'num_fields');


        $config = [
            'config' => [
                'id'     => 'fieldsets',
                'caption' => 'Field Sets',
                'data_url' => $this->BApp->href('catalogfields/fieldsets/grid_data'),
                'edit_url' => $this->BApp->href('catalogfields/fieldsets/grid_data'),
                'orm' => $orm,
                'columns' => [
                    ['type' => 'row_select'],
                    ['type' => 'btn_group', 'buttons' => [
                        [
                            'name' => 'custom',
                            'icon' => 'icon-edit-sign',
                            'cssClass' => 'btn-custom',
                            'callback' => 'showModalToEditFieldset'
                        ],
                        ['name' => 'delete']
                    ]],
                    ['name' => 'id', 'label' => 'ID', 'width' => 55, 'sorttype' => 'number', 'key' => true, 'hidden' => true],
                    ['type' => 'input', 'name' => 'set_code', 'label' => 'Set Code', 'width' => 100,  'addable' => true,
                            'editable' => true, 'validation' => ['required' => true,
                            'unique' => $this->BApp->href('catalogfields/fieldsets/unique_set')]],
                    ['type' => 'input', 'name' => 'set_name', 'label' => 'Set Name', 'width' => 200,  'addable' => true,
                            'editable' => true , 'validation' => ['required' => true]],
                    ['name' => 'num_fields', 'label' => 'Fields', 'width' => 30],
                ],
                'actions' => [
                    'add-fieldset' => [
                        'caption' => 'Add a Fieldset',
                        'type' => 'button',
                        'id' => 'add-fieldset-grid',
                        'class' => 'btn-primary',
                        'callback' => 'showModalToAddFieldset'
                    ],
//                    'new'=> array('caption'=>'Add New FieldSet', 'modal'=>true),
                    'delete' => true
                ],
                'filters' => [
                    ['field' => 'set_name', 'type' => 'text'],
                    ['field' => 'set_code', 'type' => 'text'],
                    '_quick' => ['expr' => 'product_name like ? or set_code like ', 'args' => ['%?%', '%?%']]
                ],
//                'new_button' => '#add_new_field_set'
                'callbacks' => [
                    'componentDidMount' => 'fieldsetGridRegister'
                ],
                'grid_before_create' => 'fieldsetGridRegister'
            ]
        ];

        return $config;
    }

    public function fieldsetModalSelectedGridConfig()
    {
        $config = [
            'config' => [
                'id' => 'fieldset-modal-selected-grid',
                'caption' => 'Fields',
                'data_mode' => 'local',
                'data' => [],
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'width' => 30],
                    ['name' => 'field_code', 'label' => 'Field Code', 'width' => 100, 'sortable' => false],
                    ['name' => 'field_name', 'label' => 'Field Name', 'width' => 100, 'sortable' => false],
                    ['name' => 'position', 'label' => 'Position', 'width' => 100, 'editable' => true,
                        'valdiate' => 'number', 'default' => '0', 'sortable' => false],
//                    array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'data' => array('delete' => 'noconfirm'))
                ],
                'filters' => [
                    ['field' => 'field_code', 'type' => 'text'],
                    ['field' => 'field_name', 'type' => 'text'],
                    '_quick' => ['expr' => 'field_code like ? or id like ', 'args' => ['%?%', '%?%']]
                ],
                'actions' => [
                    'delete' => ['caption' => 'Remove', 'confirm' => false]
                ],
                'afterMassDelete' => 'afterMassDeleteSelectedGrid',
                'callbacks' => [
                    'componentDidMount' => 'selectedModalGridRegister'
                ],
                'grid_before_create' => 'selectedModalGridRegister',
            ]
        ];

        return $config;
    }

    public function fieldsetModalAddGridConfig()
    {
        $config = [
            'config' => [
                'id' => 'fieldset-modal-add-grid',
                'caption' => 'Fields',
                'data_url' => $this->BApp->href('catalogfields/fieldsets/fieldset_modal_add_grid_data'),
                'orm' => 'FCom_Core_Model_Field',
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'width' => 30],
                    ['name' => 'field_code', 'label' => 'Field Code', 'width' => 100],
                    ['name' => 'field_name', 'label' => 'Field Name', 'width' => 100],
                    ['name' => 'table_field_type', 'label' => 'DB Type', 'width' => 180],
                    ['name' => 'admin_input_type', 'label' => 'Input Type', 'width' => 180]
                ],
                'filters' => [
                    ['field' => 'field_code', 'type' => 'text'],
                    ['field' => 'field_name', 'type' => 'text'],
                    '_quick' => ['expr' => 'field_code like ? or id like ', 'args' => ['%?%', '%?%']]
                ],
                'actions' => [
                    'add-linked-field' => [
                        'caption' => 'Add Selected Fields',
                        'callback' => 'addSelectedRowsToLinked',
                        'type' => 'button',
                        'id' => 'add-linked-fieldset-grid',
                        'class' => 'btn-primary',
                    ]
                ],
                'callbacks' => [
                    'componentDidMount' => 'fieldsModalGridRegister'
                ],
                'grid_before_create' => 'addFieldGridRegister',
            ]
        ];
        
        return $config;
    }

    public function fieldsGridConfig()
    {
        $fld = $this->FCom_Core_Model_Field;
        $subSql = '(select count(*) from ' . $this->FCom_Core_Model_FieldOption->table() . ' where field_id=f.id)';
        $orm = $this->FCom_Core_Model_Field->orm('f')->where('field_type', 'product')
            ->select('f.*')->select($subSql, 'num_options');

        $config = [
            'config' => [
                'id' => 'fields',
                'caption' => 'Fields',
                'orm' => $orm,
                'data_url' => $this->BApp->href('catalogfields/fieldsets/field_grid_data'),
                'edit_url' => $this->BApp->href('catalogfields/fieldsets/field_grid_data'),
                'columns' => [
                    ['type' => 'row_select'],
                    ['type' => 'btn_group', 'buttons' => [
                        [
                            'name' => 'custom',
                            'icon' => 'icon-edit-sign',
                            'cssClass' => 'btn-custom',
                            'callback' => 'showModalToEditFields'
                        ],
                        ['name' => 'delete']
                    ]],
                    ['name' => 'id', 'label' => 'ID', 'width' => 30, 'hidden' => true],
                    ['type' => 'input', 'name' => 'field_code', 'label' => 'Field Code', 'width' => 100, 'editable' => true, 'editor' => 'text',
                            'default' => '', 'addable' => true, 'multirow_edit' => true, 'validation' => ['required' => true,
                            'unique' => $this->BApp->href('/catalogfields/fields/unique_field')]],
                    ['type' => 'input', 'name' => 'field_name', 'label' => 'Field Name', 'width' => 100, 'editable' => true, 'editor' => 'text',
                            'default' => '', 'addable' => true, 'multirow_edit' => true, 'validation' => ['required' => true]],
                    ['type' => 'input', 'name' => 'frontend_label', 'label' => 'Frontend Label', 'width' => 100, 'editable' => true, 'editor' => 'text',
                            'default' => '', 'addable' => true, 'multirow_edit' => true, 'validation' => ['required' => true]],
                    ['type' => 'input', 'name' => 'frontend_show', 'label' => 'Show on frontend', 'width' => 90,
                            'editable' => true, 'addable' => true, 'multirow_edit' => true, 'validation' => ['required' => true],
                            'options' => $fld->fieldOptions('frontend_show'), 'editor' => 'select'],
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
                    ['name' => 'swatch_type', 'label' => 'Swatch Type', 'width' => 90,
                        'editable' => true, 'editor' => 'select', 'addable' => true, 'multirow_edit' => true,
                        'validation' => ['required' => true], 'options' =>  $fld->fieldOptions('swatch_type')],
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
                    ['field' => 'swatch_type', 'type' => 'multiselect'],
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
            ]
        ];
        return $config;
    }

    public function fieldLangsGridConfig()
    {
        $config = [
            'config' => [
                'id' => 'fields_options',
                'caption' => $this->_('Fields'),
                'dataUrl' => $this->BApp->href('catalogfields/fieldsets/field_option_grid_data?field_id='),
                'data_url' => $this->BApp->href('catalogfields/fieldsets/field_grid_data'),
                'edit_url' => $this->BApp->href('catalogfields/fieldsets/field_grid_data'),
                'data' => [],
                'data_mode' => 'local',
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'width' => 30, 'hidden' => true],
                    ['name' => 'lang_code', 'label' => $this->_('Language'), 'width' => 200, 'editor' => 'select',
                        'editable' => 'inline', 'type' => 'input', 'addable' => true, 'sortable' => false,
                        'options' => $this->BLocale->parseAllowedLocalesToOptions(true),
                        'select2' => true, 'placeholder' => $this->_('Select language')],
                    ['name' => 'value', 'type' => 'input', 'label' => $this->_('Value'),
                        'width' => 300, 'editable' => 'inline', 'sortable' => false],
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
                        'caption' => $this->_('Insert Language'),
                        'type' => 'button',
                        'id' => 'add_new_lang',
                        'class' => 'btn-primary',
                        'callback' => 'insertNewLang',
                    ],
                    'delete' => ['caption' => $this->_('Remove'), 'confirm' => false]
                ],
                'callbacks' => [
                    'componentDidMount' => 'langsModalGridRegister'
                ]
            ]
        ];

        return $config;
    }

    public function optionsGridConfig()
    {
        $config = [
            'config' => [
                'id' => 'fields_options',
                'caption' => 'Fields',
                'dataUrl' => $this->BApp->href('catalogfields/fieldsets/field_option_grid_data?field_id='),
                'data_url' => $this->BApp->href('catalogfields/fieldsets/field_grid_data'),
                'edit_url' => $this->BApp->href('catalogfields/fieldsets/field_grid_data'),
                'data' => [],
                'data_mode' => 'local',
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'width' => 30, 'hidden' => true],
                    ['name' => 'label', 'type' => 'input', 'label' => 'Label', 'editable' => 'inline',
                        'sortable' => false, 'validation' => ['required' => true]],
                    ['name' => 'swatch_info', 'label' => 'Swatch Color/URL', 'addable' => true,
                        'editable' => 'inline', 'sortable' => false, 'type' => 'input'],
                    ['name' => 'langs', 'label' => 'Multi Languages', 'width' => 400, 'editor' => 'select',
                        'editable' => 'inline', 'type' => 'input', 'addable' => true, 'sortable' => false,
                        'options' => $this->BLocale->parseAllowedLocalesToOptions(true), 'select2' => true,
                        'multiple' => true, 'placeholder' => 'Select some languages', 'callback' => 'addLangField'],
                    ['name' => 'lang_vals', 'type' => 'input', 'label' => 'Language Value', 'width' => 300,
                        'editable' => 'inline', 'sortable' => false],
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
                ]
            ]
        ];
/*
        $field = $this->FCom_Core_Model_Field->load($this->BRequest->get('field_id'));
        $swatchType = $field->get('swatch_type');
        if ($swatchType !== 'N') {
            switch ($swatchType) {
                case 'C':
                    $column = ['name' => 'swatch_info', 'label' => 'Swatch Color', 'addable' => true,
                        'editable' => 'inline', 'sortable' => false];
                    break;

                case 'I':
                    $column = ['name' => 'swatch_info', 'label' => 'Swatch Image URL', 'addable' => true,
                        'editable' => 'inline', 'sortable' => false];
                    break;
            }
            $config['config']['columns'] = $this->BUtil->arrayInsert($config['config']['columns'], $column,
                'arr.after.name=label');
        }
*/
        return $config;
    }

    public function action_fieldsets()
    {
        $this->layout('/catalogfields/fieldsets');
    }

    public function action_fields()
    {
        $this->layout('/catalogfields/fields');
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

    public function action_fieldset_modal_selected_grid_data()
    {
        $orm = $this->FCom_Core_Model_FieldsetField->orm('sf')
            ->join('FCom_Core_Model_Field', ['f.id', '=', 'sf.field_id'], 'f')
            ->select(['f.id', 'f.field_name', 'f.field_code', 'sf.position'])
            ->where('sf.set_id', $this->BRequest->get('set_id'));
        //TODO check when rows count is over 10.(processORM paginate)
        $data = $this->view('core/backbonegrid')->processORM($orm, __METHOD__);
        $this->BResponse->json([
            ['c' => $data['state']['c']],
            $this->BDb->many_as_array($data['rows']),
        ]);
    }

    public function action_fieldset_modal_add_grid_data()
    {
        /** @var FCom_Core_View_BackboneGrid $view */
        $view = $this->view('core/backbonegrid');
        $orm = $this->FCom_Core_Model_Field->orm()->select('*');
        $data = $view->processORM($orm, __METHOD__);
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
        $options = $this->FCom_Core_Model_FieldOption->getFieldOptions($id);

        $this->BResponse->json(
            [
                'success' => true,
                'options' => $options
            ]
        );
    }

    public function action_grid_data__POST()
    {
        $r = $this->BRequest;
        $data = $r->post();
        $field_ids = '';
        if (isset($data['field_ids'])) {
            $field_ids = $data['field_ids'];
        }

        $model = $this->FCom_Core_Model_FieldsetField;
        switch ($r->post('oper')) {
            case 'add':
                unset($data['id'], $data['oper'], $data['field_ids']);
                $set = $this->FCom_Core_Model_Fieldset->create($data)->save();
                $result = $set->as_array();
                $num_fields = 0;
                if ($field_ids !== '') {
                    $arr = explode(',', $field_ids);
                    $num_fields = count($arr);
                    foreach ($arr as $i => $fId) {
                        $model->create(['set_id' => $result['id'], 'field_id' => $fId, 'position' => $i])->save();
                    }
                }
                $result['num_fields'] = $num_fields;
                $this->BResponse->json($result);
                break;
            case 'edit':
                $model->delete_many(['set_id' => $data['id']]);
                $num_fields = 0;
                if ($field_ids !== '') {
                    $arr = explode(',', $field_ids);
                    $num_fields = count($arr);
                    foreach ($arr as $i => $fId) {
                        if (!$model->loadWhere(['set_id' => (int)$data['id'], 'field_id' => (int)$fId])) {
                            $model->create(['set_id' => $data['id'], 'field_id' => $fId, 'position' => $i])->save();
                        }
                    }
                }
                $set = $this->FCom_Core_Model_Fieldset->load($data['id']);
                unset($data['id'], $data['oper'], $data['field_ids']);
                $set->set($data)->save();
                $result = $set->as_array();
                $result['num_fields'] = $num_fields;
                $this->BResponse->json($result);
                break;
            default:
                $this->_processGridDataPost('FCom_Core_Model_Fieldset');
                break;
        }

    }

    public function action_fieldset_modal_selected_grid_data__POST()
    {
        //$this->_processPost('FCom_Core_Model_FieldsetField', array('set_id'=>$this->BRequest->get('set_id')));
        //print_r($this->BRequest->request()); exit;
        $p = $this->BRequest->post();
        $model = $this->FCom_Core_Model_FieldsetField;
        $model->delete_many(['set_id' => (int)$p['set_id']]);
        if ($p['field_ids'] !== '') {
            foreach (explode(',', $p['field_ids']) as $i => $fId) {
                $model->create(['set_id' => $p['set_id'], 'field_id' => $fId, 'position' => $i])->save();
            }
        }
        $this->BResponse->json(['success' => true]);
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
                            if (!empty($row['languages'])) {
                                foreach($row['languages'] as $lang) {
                                    $models[$row['id']]->setData(sprintf('frontend_label_translation/%s', $lang['lang_code']), $lang['value']);
                                }
                            }
                            $models[$row['id']]->set([
                                'label' => $row['label'],
                                'swatch_info' => $row['swatch_info'],
                            ])->save();
                            $op++;
                        } else { //create option
                            $rowData = [
                                'field_id' => $model->id(),
                                'label' => (string)$row['label'],
                                'swatch_info' => (string)$row['swatch_info'],
                            ];
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

        $models = $hlp->orm()->where_in('id', $this->BUtil->arrayToOptions($p['rows'], '.id'))->find_many_assoc();
        foreach ($p['rows'] as $row) {
            if (!empty($models[$row['id']])) {
                $models[$row['id']]->set([
                    'label' => $row['label'],
                    'swatch_info' => $row['swatch_info'],
                ])->save();
                $op++;
            } else {
                $data = [
                    'field_id' => (int)$p['field_id'],
                    'label' => (string)$row['label'],
                    'swatch_info' => (string)$row['swatch_info'],
                ];
                if (!$hlp->orm()->where($data)->find_one()) {
                    $hlp->create($data)->save();
                    $op++;
                }

            }
        }
        $this->BResponse->json(['success' => true, 'options' => $op]);
        //$this->_processGridDataPost('FCom_Core_Model_FieldOption', array('field_id'=>$this->BRequest->get('field_id')));
    }

    public function action_form()
    {
        $id = $this->BRequest->param('id', true);
        if ($id) {
            $model = $this->FCom_Core_Model_Fieldset->load($id);
            if (empty($model)) {
                $this->message('Invalid field set ID', 'error');
                $this->BResponse->redirect('catalogfields/fieldsets');
                return;
            }
        } else {
            $model = $this->FCom_Core_Model_Fieldset->create();
        }
        $this->layout('/catalogfields/fieldsets/form');
        $view = $this->BLayout->getView('catalogfields/fieldsets/form');
        $this->initFormTabs($view, $model, $model->id ? 'view' : 'create', $model->id ? null : 'main');
    }

    public function action_form__POST()
    {
        $r = $this->BRequest;
        $id = $r->param('id');
        $data = $r->post();

        try {
            if ($id) {
                $model = $this->FCom_Core_Model_Fieldset->load($id);
            } else {
                $model = $this->FCom_Core_Model_Fieldset->create();
            }
            $data['model'] = $this->BLocale->parseRequestDates($data['model'], 'from_date,to_date');
            $model->set($data['model']);
            $this->BEvents->fire('Sellvana_CatalogFields_Admin_Controller_FieldSets::form_post',
                ['id' => $id, 'data' => $data, 'model' => $model]);
            $model->save();
            if (!$id) {
                $id = $model->id;
            }
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
        }

        if ($r->xhr()) {
            $this->forward('form_tab', null, ['id' => $id]);
        } else {
            $this->BResponse->redirect('catalogfields/fieldsets/form/?id=' . $id);
        }
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
            $exists = $this->FCom_Core_Model_Field->orm()->where($name, $val)->find_one();
            $result = ['unique' => !$exists, 'id' => !$exists ? -1 : $exists->id()];
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage()];
        }
        $this->BResponse->json($result);
    }

    public function action_unique_set__POST()
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
            $exists = $this->FCom_Core_Model_Fieldset->orm()->where($name, $val)->find_one();
            $result = ['unique' => !$exists, 'id' => !$exists ? -1 : $exists->id()];
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage()];
        }
        $this->BResponse->json($result);
    }
}
