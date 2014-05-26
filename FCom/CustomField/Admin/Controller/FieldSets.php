<?php

class FCom_CustomField_Admin_Controller_FieldSets extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'custom_fields';

    public function fieldSetsGridConfig()
    {
        $orm = FCom_CustomField_Model_Set::i()->orm('s')->select('s.*')
            ->select('(select count(*) from ' . FCom_CustomField_Model_SetField::table() . ' where set_id=s.id)', 'num_fields');

        ;
        $config = [
            'config' => [
                'id'     => 'fieldsets',
                'caption' => 'Field Sets',
                'data_url' => BApp::href('customfields/fieldsets/grid_data'),
                'edit_url' => BApp::href('customfields/fieldsets/grid_data'),
                'orm' => $orm,
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'width' => 55, 'sorttype' => 'number', 'key' => true, 'hidden' => true],
                    ['type' => 'input', 'name' => 'set_code', 'label' => 'Set Code', 'width' => 100,  'addable' => true,
                            'editable' => true, 'validation' => ['required' => true,
                            'unique' => BApp::href('customfields/fieldsets/unique_set')]],
                    ['type' => 'input', 'name' => 'set_name', 'label' => 'Set Name', 'width' => 200,  'addable' => true,
                            'editable' => true , 'validation' => ['required' => true]],
                    ['name' => 'num_fields', 'label' => 'Fields', 'width' => 30, 'default' => '0'],
                    ['type' => 'btn_group', 'buttons' => [
                        ['name' => 'edit_custom', 'icon' => 'icon-edit-sign', 'cssClass' => 'btn-custom'],
                        ['name' => 'delete']
                    ]]
                ],
                'actions' => [
//                    'new'=> array('caption'=>'Add New FieldSet', 'modal'=>true),
                    'delete' => true
                ],
                'filters' => [
                    ['field' => 'set_name', 'type' => 'text'],
                    ['field' => 'set_code', 'type' => 'text'],
                    '_quick' => ['expr' => 'product_name like ? or set_code like ', 'args' => ['%?%', '%?%']]
                ],
                'grid_before_create' => 'customFieldsGridRegister'
//                'new_button' => '#add_new_field_set'
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
                'grid_before_create' => 'selectedFieldGridRegister'
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
                'orm' => 'FCom_CustomField_Model_Field',
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
                    'add' => ['caption' => 'Add Selected Fields']
                ],
                'grid_before_create' => 'addFieldGridRegister'
            ]
        ];

        return $config;
    }

    public function fieldsGridConfig()
    {
        $fld = FCom_CustomField_Model_Field::i();
        $orm = FCom_CustomField_Model_Field::i()->orm('f')->select('f.*')
            ->select('(select count(*) from ' . FCom_CustomField_Model_FieldOption::table() . ' where field_id=f.id)', 'num_options')
        ;
        $config = [
            'config' => [
                'id' => 'fields',
                'caption' => 'Fields',
                'orm' => $orm,
                'data_url' => BApp::href('customfields/fieldsets/field_grid_data'),
                'edit_url' => BApp::href('customfields/fieldsets/field_grid_data'),
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'width' => 30, 'hidden' => true],
                    ['type' => 'input', 'name' => 'field_code', 'label' => 'Field Code', 'width' => 100, 'editable' => true, 'editor' => 'text',
                            'defualt' => '', 'addable' => true, 'mass-editable' => true, 'validation' => ['required' => true,
                            'unique' => BApp::href('/customfields/fields/unique_field')]],
                    ['type' => 'input', 'name' => 'field_name', 'label' => 'Field Name', 'width' => 100, 'editable' => true, 'editor' => 'text',
                            'default' => '', 'addable' => true, 'mass-editable' => true, 'validation' => ['required' => true]],
                    ['type' => 'input', 'name' => 'frontend_label', 'label' => 'Frontend Label', 'width' => 100, 'editable' => true, 'editor' => 'text',
                            'default' => '', 'addable' => true, 'mass-editable' => true, 'validation' => ['required' => true]],
                    ['type' => 'input', 'name' => 'frontend_show', 'label' => 'Show on frontend', 'width' => 90, 'editor' => 'text',
                            'editable' => true, 'addable' => true, 'mass-editable' => true, 'validation' => ['required' => true],
                            'options' => $fld->fieldOptions('frontend_show'), 'editor' => 'select'],
                    ['type' => 'input', 'name' => 'sort_order', 'label' => 'Sort order', 'width' => 30, 'editable' => true, 'editor' => 'text',
                            /*'editor'=>'select',*/ 'validate' => 'number', 'addable' => true,
                            'mass-editable' => true, 'validation' => ['required' => true]/*,
                            'options'=>range(0,20)*/],
                            /*'facet_select'=>array('label'=>'Facet', 'width'=>200, 'editable'=>true,
                                'options'=>array('No'=>'No', 'Exclusive'=>'Exclusive', 'Inclusive'=>'Inclusive')),*/
                    ['type' => 'input', 'name' => 'table_field_type', 'label' => 'DB Type', 'width' => 180, 'editor' => 'select',
                            'addable' => true, 'validation' => ['required' => true], 'options' => $fld->fieldOptions('table_field_type')],
                    ['type' => 'input', 'name' => 'admin_input_type', 'label' => 'Input Type', 'width' => 180,
                        'editable' => true, 'editor' => 'select', 'addable' => true, 'mass-editable' => true,
                        'validation' => ['required' => true], 'options' => $fld->fieldOptions('admin_input_type')],
                    ['type' => 'input', 'name' => 'num_options', 'label' => 'Options', 'width' => 30, 'default' => '0'],
                    ['type' => 'input', 'name' => 'system', 'label' => 'System field', 'width' => 90, 'editable' => false, 'editor' => 'select',
                         'addable' => true, 'mass-editable' => true, 'validation' => ['required' => true], 'options' => ['0' => 'No', '1' => 'Yes']],
                    ['type' => 'input', 'name' => 'multilanguage', 'label' => 'Multi Language', 'width' => 90,
                        'editable' => true, 'editor' => 'select', 'addable' => true, 'mass-editable' => true,
                        'validation' => ['required' => true], 'options' => ['0' => 'No', '1' => 'Yes']],
                    ['type' => 'input', 'name' => 'required', 'label' => 'Required', 'width' => 90, 'editable' => true,
                        'editor' => 'select', 'addable' => true, 'mass-editable' => true, 'validation' => ['required' => true],
                        'options' => ['1' => 'Yes', '0' => 'No']],
                    ['type' => 'btn_group', 'buttons' => [
                        ['name' => 'custom', 'caption' => 'options...', 'cssClass' => 'btn-custom'],
                        ['name' => 'edit'],
                        ['name' => 'delete']
                    ]]
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
                    //'new'=>array('caption'=>'Add a field', 'modal'=>true),
                    'edit' => true,
                    'delete' => true
                ],
                //'callbacks'=>array('after_render'=>'afterRowRenderFieldsGrid'),
                'grid_before_create' => 'fieldsGridRegister',
                'new_button' => '#add_new_field'
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
                'data_mode' => 'local',
                'data' => [],
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
                    'new' => ['caption' => 'Insert New Option'],
                    'delete' => ['caption' => 'Remove', 'confirm' => false]
                ],
                'grid_before_create' => 'optionsGridRegister'
            ]
        ];

        return $config;
    }

    public function action_fieldsets()
    {
        $this->layout('/customfields/fieldsets');
    }

    public function action_fields()
    {
        $this->layout('/customfields/fields');
    }

    public function action_grid_data()
    {
        $view = $this->view('core/backbonegrid');
        $view->set('grid', $this->fieldSetsGridConfig());
        $data = $view->generateOutputData();
        BResponse::i()->json([
            ['c' => $data['state']['c']],
            BDb::many_as_array($data['rows']),
        ]);
    }

    public function action_set_field_grid_data()
    {
        $orm = FCom_CustomField_Model_SetField::i()->orm('sf')
            ->join('FCom_CustomField_Model_Field', ['f.id', '=', 'sf.field_id'], 'f')
            ->select(['f.id', 'f.field_name', 'f.field_code', 'sf.position'])
            ->where('sf.set_id', BRequest::i()->get('set_id'));
        //TODO check when rows count is over 10.(processORM paginate)
        $data = $this->view('core/backbonegrid')->processORM($orm, __METHOD__);
        BResponse::i()->json([
            ['c' => $data['state']['c']],
            BDb::many_as_array($data['rows']),
        ]);
    }

    public function action_field_grid_data()
    {
        $view = $this->view('core/backbonegrid');
        $view->set('grid', $this->fieldsGridConfig());
        $data = $view->generateOutputData();
        BResponse::i()->json([
            ['c' => $data['state']['c']],
            BDb::many_as_array($data['rows']),
        ]);
    }

    public function action_field_option_grid_data()
    {
        $orm = FCom_CustomField_Model_FieldOption::i()->orm('fo')->select('fo.*')
            ->where('field_id', BRequest::i()->get('field_id'));
        $data = $this->view('core/backbonegrid')->processORM($orm, __METHOD__);
        BResponse::i()->json([
            ['c' => $data['state']['c']],
            BDb::many_as_array($data['rows']),
        ]);
    }

    public function action_options()
    {
        $id = BRequest::i()->get('id');
        $options = FCom_CustomField_Model_FieldOption::i()->getListAssocById($id);

        BResponse::i()->json(
            [
                'success' => true,
                'options' => $options
            ]
        );
    }

    public function action_grid_data__POST()
    {
        $r = BRequest::i();
        $data = $r->post();
        $field_ids = $data['field_ids'];
        $model = FCom_CustomField_Model_SetField::i();
        switch ($r->post('oper')) {
            case 'add':
                unset($data['id'], $data['oper'], $data['field_ids']);
                $set = FCom_CustomField_Model_Set::i()->create($data)->save();
                $result = $set->as_array();
                $mum_fields = 0;
                if ($field_ids !== '') {
                    $arr = explode(',', $field_ids);
                    $mum_fields = count($arr);
                    foreach ($arr as $i => $fId) {
                        $model->create(['set_id' => $result['id'], 'field_id' => $fId, 'position' => $i])->save();
                    }
                }
                $result['num_fields'] = $mum_fields;
                BResponse::i()->json($result);
                break;
            case 'edit':
                $model->delete_many(['set_id' => $data['id']]);
                if ($field_ids !== '') {
                    $arr = explode(',', $field_ids);
                    foreach ($arr as $i => $fId) {
                        if (!$model->loadWhere(['set_id' => (int)$data['id'], 'field_id' => (int)$fId])) {
                            $model->create(['set_id' => $data['id'], 'field_id' => $fId, 'position' => $i])->save();
                        }
                    }
                }
                $set = FCom_CustomField_Model_Set::i()->load($data['id']);
                unset($data['id'], $data['oper'], $data['field_ids']);
                $set->set($data)->save();
                $result = $set->as_array();
                BResponse::i()->json($result);
                break;
            default:
                $this->_processGridDataPost('FCom_CustomField_Model_Set');
                break;
        }

    }

    public function action_set_field_grid_data__POST()
    {
        //$this->_processPost('FCom_CustomField_Model_SetField', array('set_id'=>BRequest::i()->get('set_id')));
        //print_r(BRequest::i()->request()); exit;
        $p = BRequest::i()->post();
        $model = FCom_CustomField_Model_SetField::i();
        $model->delete_many(['set_id' => $p['set_id']]);
        if ($p['field_ids'] !== '') {
            foreach (explode(',', $p['field_ids']) as $i => $fId) {
                $model->create(['set_id' => $p['set_id'], 'field_id' => $fId, 'position' => $i])->save();
            }
        }
        BResponse::i()->json(['success' => true]);
    }

    public function action_field_grid_data__POST()
    {
        $this->_processGridDataPost('FCom_CustomField_Model_Field');
    }

    public function action_field_option_grid_data__POST()
    {
        $p = BRequest::i()->post();
        $model = FCom_CustomField_Model_FieldOption::i();
        $op = 0;
//        $model->delete_many(['field_id' => $p['field_id']]);
        foreach ($p['rows'] as $row) {
            $fieldOption = $model->orm()->where('id', $row['id'])->find_one();
            if ($fieldOption) {
                $fieldOption->set('label', $row['label'])->save();
                $op++;
            } else {
                $data = ['field_id' => $p['field_id'], 'label' => $row['label']];
                if (!$model->orm()->where($data)->find_one()) {
                    $model->create($data)->save();
                    $op++;
                }

            }
        }
        BResponse::i()->json(['success' => true, 'options' => $op]);
        //$this->_processGridDataPost('FCom_CustomField_Model_FieldOption', array('field_id'=>BRequest::i()->get('field_id')));
    }

    public function action_form()
    {
        $id = BRequest::i()->params('id');
        if (!$id) {
            $id = BRequest::i()->get('id');
        }
        if ($id) {
            $model = FCom_CustomField_Model_Set::i()->load($id);
            if (empty($model)) {
                $this->message('Invalid field set ID', 'error');
                BResponse::i()->redirect('customfields/fieldsets');
                return;
            }
        } else {
            $model = FCom_CustomField_Model_Set::i()->create();
        }
        $this->layout('/customfields/fieldsets/form');
        $view = BLayout::i()->view('customfields/fieldsets/form');
        $this->initFormTabs($view, $model, $model->id ? 'view' : 'create', $model->id ? null : 'main');
    }

    public function action_form__POST()
    {
        $r = BRequest::i();
        $id = $r->params('id');
        $data = $r->post();

        try {
            if ($id) {
                $model = FCom_CustomField_Model_Set::i()->load($id);
            } else {
                $model = FCom_CustomField_Model_Set::i()->create();
            }
            $data['model'] = BLocale::i()->parseRequestDates($data['model'], 'from_date,to_date');
            $model->set($data['model']);
            BEvents::i()->fire('FCom_CustomField_Admin_Controller_FieldSets::form_post',
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
            BResponse::i()->redirect('customfields/customfield/form/?id=' . $id);
        }
    }

    public function action_unique_field__POST()
    {
        $r = BRequest::i();
        $p = $r->post();
        $name = $p['_name'];
        $val = $p[$name];
        $rows = BDb::many_as_array(FCom_CustomField_Model_Field::i()->orm()->where($name, $val)->find_many());
        BResponse::i()->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }

    public function action_unique_set__POST()
    {
        $r = BRequest::i();
        $p = $r->post();
        $name = $p['_name'];
        $val = $p[$name];
        $rows = BDb::many_as_array(FCom_CustomField_Model_Set::i()->orm()->where($name, $val)->find_many());
        BResponse::i()->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }
}
