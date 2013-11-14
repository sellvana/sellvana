<?php

class FCom_CustomField_Admin_Controller_FieldSets extends FCom_Admin_Controller_Abstract
{
    public function fieldSetsGridConfig()
    {
        $data = FCom_CustomField_Model_Set::i()->orm('s')->select('s.*')
            ->select('(select count(*) from '.FCom_CustomField_Model_SetField::table().' where set_id=s.id)', 'num_fields')
            ->find_many();
        ;
        $config = array(
            'config'=>array(
                'id'     =>'fieldsets',
                'caption'=>'Field Sets',
                'data_url'=>BApp::href('customfields/fieldsets/grid_data'),
                'edit_url'=>BApp::href('customfields/fieldsets/grid_data'),
                'data_mode'=>'local',
                'data'=>$data,
                'columns'=>array(
                    array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>40),
                    array('name'=>'id','label'=>'ID', 'width'=>55, 'sorttype'=>'number', 'key'=>true),
                    array('name'=>'set_code', 'label'=>'Set Code', 'width'=>100, 'editable'=>true),
                    array('name'=>'set_name', 'label'=>'Set Name', 'width'=>200, 'editable'=>true),
                    array('name'=>'num_fields', 'label'=>'Fields', 'width'=>30, 'default'=>'0'),
                    array('name'=>'_actions', 'label'=>'Actions', 'sortable'=>false, 'data'=>array('edit'=>array('async_edit'=>true),'delete'=>true))
                ),
                'actions'=>array(
                            'new'=> array('caption'=>'Add'),
                            'edit'=>true,
                            'delete'=>true
                ),
                'filters'=>array(
                            array('field'=>'set_name', 'type'=>'text'),
                            array('field'=>'set_code', 'type'=>'text'),
                            '_quick'=>array('expr'=>'product_name like ? or set_code like ', 'args'=> array('%?%', '%?%'))
                ),
                'events'=>array('async_edit')
            )
        );

        return $config;
    }

    public function fieldsetModalSelectedGridConfig()
    {
        $config = array(
            'config'=>array(
                'id'=>'fieldset-modal-selected-grid',
                'caption'=>'Fields',
                'data_mode'=>'local',
                'data'=>array(),
                'columns'=>array(
                    array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>30),
                    array('name'=>'id', 'label'=>'ID', 'width'=>30),
                    array('name'=>'field_code', 'label'=>'Field Code', 'width'=>100),
                    array('name'=>'field_name', 'label'=>'Field Name', 'width'=>100),
                    array('name'=>'position', 'label'=>'Position', 'width'=>100, 'editable'=>true, 'valdiate'=>'number', 'default'=>'0'),
                    array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'data' => array('delete' => true))
                ),
                'filters'=>array(
                            array('field'=>'field_code', 'type'=>'text'),
                            array('field'=>'field_name', 'type'=>'text'),
                            '_quick'=>array('expr'=>'field_code like ? or id like ', 'args'=> array('%?%', '%?%'))
                ),
                'actions'=>array(
                                    'delete' => array('caption' => 'Remove')
                                )
            )
        );

        return $config;
    }

    public function fieldsetModalAddGridConfig()
    {
        $config = array(
            'config'=>array(
                'id'=>'fieldset-modal-add-grid',
                'caption'=>'Fields',
                'orm'=>'FCom_CustomField_Model_Field',
                'columns'=>array(
                    array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>30),
                    array('name'=>'id', 'label'=>'ID', 'width'=>30),
                    array('name'=>'field_code', 'label'=>'Field Code', 'width'=>100),
                    array('name'=>'field_name', 'label'=>'Field Name', 'width'=>100),
                    array('name'=>'table_field_type', 'label'=>'DB Type', 'width'=>180),
                    array('name'=>'admin_input_type', 'label'=>'Input Type', 'width'=>180)
                ),
                'filters'=>array(
                            array('field'=>'field_code', 'type'=>'text'),
                            array('field'=>'field_name', 'type'=>'text'),
                            '_quick'=>array('expr'=>'field_code like ? or id like ', 'args'=> array('%?%', '%?%'))
                ),
                'actions'=>array(
                                    'add' => array('caption' => 'Add fields'),
                                ),
                'events'=>array('add')
            )
        );

        return $config;
    }

    public function fieldsGridConfig()
    {
        $fld = FCom_CustomField_Model_Field::i();
        $orm = FCom_CustomField_Model_Field::i()->orm('f')->select('f.*')
            ->select('(select count(*) from '.FCom_CustomField_Model_FieldOption::table().' where field_id=f.id)', 'num_options')
        ;
        $config = array(
            'config'=>array(
                'id'=>'fields',
                'caption'=>'Fields',
                'orm'=>$orm,
                'data_url'=>BApp::href('customfields/fieldsets/field_grid_data'),
                'edit_url'=>BApp::href('customfields/fieldsets/field_grid_data'),
                'columns'=>array(
                    array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>30),
                    array('name'=>'id', 'label'=>'ID', 'width'=>30),
                    array('name'=>'field_code', 'label'=>'Field Code', 'width'=>100, 'editable'=>'new', 'defualt'=>''),
                    array('name'=>'field_name', 'label'=>'Field Name', 'width'=>100, 'editable'=>true, 'default'=>''),
                    array('name'=>'frontend_label', 'label'=>'Frontend Label', 'width'=>100, 'editable'=>true, 'default'=>''),
                    array('name'=>'frontend_show', 'label'=>'Show on frontend', 'width'=>90, 'editable'=>true,
                        'options'=>$fld->fieldOptions('frontend_show'), 'editor'=>'select'),
                    array('name'=>'sort_order', 'label'=>'Sort order', 'width'=>30, 'editable'=>true,/*'editor'=>'select',*/ 'validate'=>'number'/*,
                    'options'=>range(0,20)*/),
                    /*'facet_select'=>array('label'=>'Facet', 'width'=>200, 'editable'=>true,
                        'options'=>array('No'=>'No', 'Exclusive'=>'Exclusive', 'Inclusive'=>'Inclusive')),*/
                    array('name'=>'table_field_type', 'label'=>'DB Type', 'width'=>180, 'editable'=>true,'editor'=>'select',
                    'options'=>$fld->fieldOptions('table_field_type')),
                    array('name'=>'admin_input_type', 'label'=>'Input Type', 'width'=>180, 'editable'=>true,'editor'=>'select',
                        'options'=>$fld->fieldOptions('admin_input_type')),
                    array('name'=>'num_options', 'label'=>'Options', 'width'=>30, 'default'=>'0'),
                    array('name'=>'system', 'label'=>'System field', 'width'=>90, 'editable'=>false, 'editor'=>'select',
                        'options'=>array('0'=>'No', '1'=>'Yes')),
                    array('name'=>'_actions', 'label'=>'Actions', 'sortable'=>false, 'data'=>array('edit'=>array('async_edit'=>true),'delete'=>true))
                ),
                'filters'=>array(
                            array('field'=>'field_name', 'type'=>'text'),
                            array('field'=>'table_field_type', 'type'=>'multiselect'),
                            array('field'=>'admin_input_type', 'type'=>'multiselect'),
                            '_quick'=>array('expr'=>'field_code like ? or id like ', 'args'=> array('%?%', '%?%'))
                ),
                'actions'=>array(
                                    'new'=>array('caption'=>'Add a field'),
                                    'edit'=>true,
                                    'delete'=>true
                                ),
                'events'=>array('async_edit'),
                'callbacks'=>array('after_render'=>'afterRowRenderFieldsGrid')
            )
        );
        return $config;
    }

    public function optionsGridConfig()
    {
        $config = array(
            'config'=>array(
                'id'=>'options-grid',
                'caption'=>'Fields',
                'data_mode'=>'local',
                'data'=>array(),
                'columns'=>array(
                    array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>30),
                    array('name'=>'id', 'label'=>'ID', 'width'=>30),
                    array('name'=>'label', 'label'=>'Label', 'width'=>300, 'editable'=>true),
                    array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'data' => array('delete' => true))
                ),
                'filters'=>array(
                            '_quick'=>array('expr'=>'field_code like ? or id like ', 'args'=> array('%?%', '%?%'))
                ),
                'actions'=>array(
                                    'new' => array('caption' => 'Insert New Option'),
                                    'delete' => array('caption' => 'Remove')
                                ),
                'events'=>array('init')
            )
        );

        return $config;
    }

    public function action_index()
    {
        $this->layout('/customfields/fieldsets');
    }

    public function action_grid_data()
    {
        $orm = FCom_CustomField_Model_Set::i()->orm('s')->select('s.*')
            ->select('(select count(*) from '.FCom_CustomField_Model_SetField::table().' where set_id=s.id)', 'num_fields')
        ;
        $data = $this->view('core/backbonegrid')->processORM($orm, __METHOD__);
        BResponse::i()->json(array(
                    array('c' => $data['state']['c']),
                    BDb::many_as_array($data['rows']),
                ));
    }

    public function action_set_field_grid_data()
    {
        $orm = FCom_CustomField_Model_SetField::i()->orm('sf')
            ->join('FCom_CustomField_Model_Field', array('f.id','=','sf.field_id'), 'f')
            ->select(array('f.id', 'f.field_name', 'f.field_code', 'sf.position'))
            ->where('sf.set_id', BRequest::i()->get('set_id'));
        //TODO check when rows count is over 10.(processORM paginate)
        $data = $this->view('core/backbonegrid')->processORM($orm, __METHOD__);
        BResponse::i()->json(array(
                    array('c' => $data['state']['c']),
                    BDb::many_as_array($data['rows']),
                ));
    }

    public function action_field_grid_data()
    {
        $orm = FCom_CustomField_Model_Field::i()->orm('f')->select('f.*')
            ->select('(select count(*) from '.FCom_CustomField_Model_FieldOption::table().' where field_id=f.id)', 'num_options')
        ;
        $data = $this->view('core/backbonegrid')->processORM($orm, __METHOD__);
        BResponse::i()->json(array(
                    array('c' => $data['state']['c']),
                    BDb::many_as_array($data['rows']),
                ));
    }

    public function action_field_option_grid_data()
    {
        $orm = FCom_CustomField_Model_FieldOption::i()->orm('fo')->select('fo.*')
            ->where('field_id', BRequest::i()->get('field_id'));
        $data = $this->view('core/backbonegrid')->processORM($orm, __METHOD__);
        BResponse::i()->json(array(
                    array('c' => $data['state']['c']),
                    BDb::many_as_array($data['rows']),
                ));
    }

    public function action_grid_data__POST()
    {
        $this->_processGridDataPost('FCom_CustomField_Model_Set');
    }

    public function action_set_field_grid_data__POST()
    {
        //$this->_processPost('FCom_CustomField_Model_SetField', array('set_id'=>BRequest::i()->get('set_id')));
        //print_r(BRequest::i()->request()); exit;
        $p = BRequest::i()->post();
        $model = FCom_CustomField_Model_SetField::i();
        $model->delete_many(array('set_id'=>$p['set_id']));
        foreach (explode(',', $p['field_ids']) as $i=>$fId) {
            $model->create(array('set_id'=>$p['set_id'], 'field_id'=>$fId, 'position'=>$i))->save();
        }
        BResponse::i()->json(array('success'=>true));
    }

    public function action_field_grid_data__POST()
    {
        $this->_processGridDataPost('FCom_CustomField_Model_Field');
    }

    public function action_field_option_grid_data__POST()
    {
        $p = BRequest::i()->post();
        $model = FCom_CustomField_Model_FieldOption::i();
        $model->delete_many(array('field_id'=>$p['field_id']));
        foreach ($p['rows'] as $row) {
            $row['field_id'] = $p['field_id'];
            $model->create($row)->save();
        }
        BResponse::i()->json(array('success'=>true));
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
                BSession::i()->addMessage('Invalid field set ID', 'error', 'admin');
                BResponse::i()->redirect(BApp::href('customfields/fieldsets'));
            }
        } else {
            $model = FCom_CustomField_Model_Set::i()->create();
        }
        $this->layout('/customfields/fieldsets/form');
        $view = BLayout::i()->view('customfields/fieldsets/form');
        $this->initFormTabs($view, $model, $model->id ? 'view' : 'create', $model->id ? null : 'main');
    }

    public function action_form_tab()
    {
        $r = BRequest::i();
        $id = $r->params('id');
        if (!$id) {
            $id = $r->request('id');
        }
        $this->layout('denteva_promo_form_tabs');
        $view = BLayout::i()->view('denteva/promo/form');
        $promo = Denteva_Model_Promo::i()->load($id);
        $this->outFormTabsJson($view, $promo);
    }

    public function action_form__POST()
    {
        $r = BRequest::i();
        $id = $r->params('id');
        $data = $r->post();

        try {
            if ($id) {
                $model = Denteva_Model_Promo::i()->load($id);
            } else {
                $model = Denteva_Model_Promo::i()->create();
            }
            $data['model'] = BLocale::i()->parseRequestDates($data['model'], 'from_date,to_date');
            $model->set($data['model']);
            BEvents::i()->fire('FCom_CustomField_Admin_Controller_FieldSets::form_post', array('id'=>$id, 'data'=>$data, 'model'=>$model));
            $model->save();
            if (!$id) {
                $id = $model->id;
            }
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }

        if ($r->xhr()) {
            $this->forward('form_tab', null, array('id'=>$id));
        } else {
            BResponse::i()->redirect(BApp::href('customfields/customfield/form/?id='.$id));
        }
    }

}
