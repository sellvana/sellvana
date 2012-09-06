<?php

class FCom_CustomField_Admin_Controller_FieldSets extends FCom_Admin_Controller_Abstract
{
    public function fieldSetsGridConfig()
    {
        $config = array(
            'grid' => array(
                'id'      => __CLASS__,
                'caption' => 'Field Sets',
                'url'     => BApp::href('customfields/fieldsets/grid_data'),
                'editurl' => BApp::href('customfields/fieldsets/grid_data'),
                'columns' => array(
                    'id' => array('label'=>'ID', 'width'=>55, 'sorttype'=>'number', 'key'=>true),
                    'set_code' => array('label'=>'Set Code', 'width'=>100, 'editable'=>true),
                    'set_name' => array('label'=>'Set Name', 'width'=>200, 'editable'=>true),
                    'num_fields' => array('label' => 'Fields', 'width'=>30),
                ),
            ),
            'subGrid' => array(
                'grid' => array(
                    'url' => BApp::href('customfields/fieldsets/set_field_grid_data?set_id='),
                    'columns' => array(
                        'id' => array('label'=>'ID', 'hidden'=>true, 'width'=>30),
                        'field_code' => array('label'=>'Field', 'width'=>200),
                        'field_name' => array('label'=>'Name', 'width'=>200),
                    ),
                    'multiselect' => true,
                    'autowidth' => false,
                    'rowList' => 1000,
                    'rowNum' => 1000,
                ),
                'navGrid'=>array('add'=>true, 'del'=>true, 'refresh'=>false, 'search'=>false,
                    'addtext'=>'Add', 'addtitle'=>'Add a field from list', 'addfunc'=>"function() {
var subgrid = \$(this).closest('.ui-jqgrid').find('.ui-jqgrid-btable');
var src = \$('#fields'), sel = src.jqGrid('getGridParam', 'selarrrow'), i;
if (!sel.length) {
    alert('Please select some fields to add on the right');
    return;
}
for (i=0; i<sel.length; i++) {
    subgrid.jqGrid('addRowData', sel[i], src.jqGrid('getRowData', sel[i]));
}
src.jqGrid('resetSelection');
updateFieldSet(subgrid);
                    }",
                    'delfunc'=>"function() {
var subgrid = \$(this).closest('.ui-jqgrid').find('.ui-jqgrid-btable');
var sel = subgrid.jqGrid('getGridParam', 'selarrrow'), i;
if (!sel.length) {
    alert('Please select some fields to remove');
    return;
}
for (i=sel.length-1; i>=0; i--) {
    subgrid.jqGrid('delRowData', sel[i]);
}
updateFieldSet(subgrid);
                    }",
                ),
                #'sortableRows'=>array('forcePlaceholderSize'=>true, 'helper'=>'clone', 'containment'=>'parent'),
                'custom' => array(
                    'jsBefore' => "
var data = [], fields = \$('#fieldsets').jqGrid('getRowData', row_id).field_codes;
var src = fields ? fields.split(',') : [], i;
for (i=0; i<src.length; i++) data.push({id:src[i], field_code:src[i]});
                    ",
                ),
            ),
            'custom' => array('personalize'=>true),
            'navGrid' => array('add'=>true, 'edit'=>true, 'del'=>true,
                'addtext'=>'New', 'addtitle'=>'Create new Field Set',
            ),
            #'inlineNav' => array(),
            #'searchGrid' => array('multipleSearch'=>true, 'multipleGroup'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
        );
        BPubSub::i()->fire(__METHOD__, array('config'=>&$config));
        return $config;
    }

    public function fieldsGridConfig()
    {
        $fld = FCom_CustomField_Model_Field::i();
        $config = array(
            'grid' => array(
                'id' => 'fields',
                'caption' => 'Fields',
                'url' => BApp::href('customfields/fieldsets/field_grid_data'),
                'editurl' => BApp::href('customfields/fieldsets/field_grid_data'),
                'columns' => array(
                    'id' => array('label'=>'ID', 'width'=>30),
                    'field_code' => array('label'=>'Field Code', 'width'=>200, 'editable'=>true),
                    'field_name' => array('label'=>'Field Name', 'width'=>200, 'editable'=>true),
                    'frontend_label' => array('label'=>'Frontend Label', 'width'=>200, 'editable'=>true),
                    'frontend_show' => array('label'=>'Show on frontend', 'width'=>50, 'editable'=>true,
                        'options'=>$fld->fieldOptions('frontend_show')),
                    'table_field_type' => array('label'=>'DB Type', 'width'=>80, 'editable'=>true,
                        'options'=>$fld->fieldOptions('table_field_type')),
                    'admin_input_type' => array('label'=>'Input Type', 'width'=>80, 'editable'=>true,
                        'options'=>$fld->fieldOptions('admin_input_type')),
                    'num_options' => array('label' => 'Options', 'width'=>30),
                ),
                'multiselect' => true,
            ),
            'subGrid' => array(
                'grid' => array(
                    'url' => BApp::href('customfields/fieldsets/field_option_grid_data?field_id='),
                    'editurl' => BApp::href('customfields/fieldsets/field_option_grid_data?field_id='),
                    'columns' => array(
                        'id' => array('label'=>'ID', 'width'=>30),
                        'label' => array('label'=>'Label', 'width'=>300, 'editable'=>true),
                    ),
                    'multiselect' => true,
                    'autowidth' => false,
                ),
                'navGrid'=>array('add'=>true, 'edit'=>true, 'del'=>true, 'refresh'=>true, 'search'=>false,
                    'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
                ),
            ),
            'navGrid' => array('add'=>true, 'addtext'=>'New', 'addtitle'=>'Create new Field', 'edit'=>true, 'del'=>true),
            'custom' => array('personalize'=>true),
            #'inlineNav' => array(),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
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
        $data = $this->view('jqgrid')->processORM($orm, __METHOD__);
        BResponse::i()->json($data);
    }

    public function action_set_field_grid_data()
    {
        $orm = FCom_CustomField_Model_SetField::i()->orm('sf')
            ->join('FCom_CustomField_Model_Field', array('f.id','=','sf.field_id'), 'f')
            ->select(array('f.id', 'f.field_name', 'f.field_code'))
            ->where('sf.set_id', BRequest::i()->get('set_id'));
        $data = $this->view('jqgrid')->processORM($orm, __METHOD__);
        BResponse::i()->json($data);
    }

    public function action_field_grid_data()
    {
        $orm = FCom_CustomField_Model_Field::i()->orm('f')->select('f.*')
            ->select('(select count(*) from '.FCom_CustomField_Model_FieldOption::table().' where field_id=f.id)', 'num_options')
        ;
        $data = $this->view('jqgrid')->processORM($orm, __METHOD__);
        BResponse::i()->json($data);
    }

    public function action_field_option_grid_data()
    {
        $orm = FCom_CustomField_Model_FieldOption::i()->orm('fo')->select('fo.*')
            ->where('field_id', BRequest::i()->get('field_id'));
        $data = $this->view('jqgrid')->processORM($orm, __METHOD__);
        BResponse::i()->json($data);
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
        $this->_processGridDataPost('FCom_CustomField_Model_FieldOption', array('field_id'=>BRequest::i()->get('field_id')));
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
        $this->initFormTabs($view, $model, $model->id ? 'view' : 'create', $promo->id ? null : 'main');
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
            BPubSub::i()->fire('FCom_CustomField_Admin_Controller_FieldSets::form_post', array('id'=>$id, 'data'=>$data, 'model'=>$model));
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