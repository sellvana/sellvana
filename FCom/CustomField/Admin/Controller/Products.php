<?php

class FCom_CustomField_Admin_Controller_Products extends FCom_Admin_Controller_Abstract
{
    public function fieldsetsGridConfig()
    {
        $config = array(
            'grid' => array(
                'id'      => 'product_fieldsets',
                'caption' => 'Field Sets',
                'url' => BApp::href('customfields/fieldsets/grid_data'),
                'orm' => 'FCom_CustomField_Model_SetField',
                'columns' => array(
                    'id' => array('label'=>'ID', 'width'=>55, 'sorttype'=>'number', 'key'=>true),
                    'set_code' => array('label'=>'Set Code', 'width'=>100, 'editable'=>true),
                    'set_name' => array('label'=>'Set Name', 'width'=>200, 'editable'=>true),
                    'num_fields' => array('label' => 'Fields', 'width'=>30),
                ),
                'actions' => array(
                            'edit' => true,
                            'delete' => true
                ),
                'filters' => array(
                            array('field' => 'set_name', 'type' => 'text'),
                            array('field' => 'set_code', 'type' => 'text'),
                            '_quick' => array('expr' => 'product_name like ? or set_code like ', 'args' =>  array('%?%', '%?%'))
                )
            )
        );

        return $config;
    }

    public function fieldsGridConfig()
    {
        $config = FCom_CustomField_Admin_Controller_FieldSets::i()->fieldsGridConfig();
        $config['grid']['id'] = __CLASS__;
        $config['grid']['hiddengrid'] = true;
        $config[] = array('navButtonAdd', 'caption'=>'Add', 'buttonicon'=>'ui-icon-plus', 'position'=>'first',
            'title'=>'Add fields to product', 'onClickButton'=>'function() { return addCustomFields.call(this) }');
        return $config;
    }

    public function formViewBefore()
    {
        $id = BRequest::i()->params('id', true);
        $p = FCom_Catalog_Model_Product::i()->load($id);

        if (!$p) {
            return;//$p = FCom_Catalog_Model_Product::i()->create();
        }

        $fields_options = array();
        $fields = FCom_CustomField_Model_ProductField::i()->productFields($p);
        foreach($fields as $field){
            $fields_options[$field->id] = FCom_CustomField_Model_FieldOption::i()->orm()
                    ->where("field_id", $field->id)->find_many();
        }
        $view = $this->view('customfields/products/fields-partial');
        $view->set('model', $p)->set('fields', $fields)->set('fields_options', $fields_options);
    }

    public function action_field_remove()
    {
        $id = BRequest::i()->params('id', true);
        $p = FCom_Catalog_Model_Product::i()->load($id);
        if(!$p){
            return;
        }
        $hide_field = BRequest::i()->params('hide_field', true);
        if(!$hide_field){
            return;
        }
        FCom_CustomField_Model_ProductField::i()->removeField($p, $hide_field);
        BResponse::i()->json('');
        exit;
    }

    public function action_fields_partial()
    {
        $id = BRequest::i()->params('id', true);
        $p = FCom_Catalog_Model_Product::i()->load($id);
        if (!$p) {
            $p = FCom_Catalog_Model_Product::i()->create();
        }

        $fields_options = array();
        $fields = FCom_CustomField_Model_ProductField::i()->productFields($p, BRequest::i()->request());
        foreach($fields as $field){
            $fields_options[$field->id] = FCom_CustomField_Model_FieldOption::i()->orm()
                    ->where("field_id", $field->id)->find_many();
        }

        $view = $this->view('customfields/products/fields-partial');
        $view->set('model', $p)->set('fields', $fields)->set('fields_options', $fields_options);
        BLayout::i()->rootView('customfields/products/fields-partial');
        BResponse::i()->render();
    }

    public function getInitialData($id)
    {
        $res = BDb::many_as_array(FCom_CustomField_Model_ProductField::i()->orm()->where('product_id',$id)->find_many());
        return $res[0]['_data_serialized'];
    }
    public function fieldsetAry()
    {
        $sets= BDb::many_as_array(FCom_CustomField_Model_Set::i()->orm('s')->select('s.*')->find_many());

        return json_encode($sets);
    }

    public function fieldAry()
    {
        $fields = BDb::many_as_array(FCom_CustomField_Model_SetField::i()->orm('s')->select('s.*')->find_many());

        return json_encode($fields);
    }

    public function action_get_fieldset()
    {
        $r = BRequest::i();
        $id = $r->get('id');
        $set = FCom_CustomField_Model_Set::i()->load($id);
        $fields = BDb::many_as_array(
                    FCom_CustomField_Model_SetField::i()->orm('sf')
                    ->join('FCom_CustomField_Model_Field', array('f.id','=','sf.field_id'), 'f')
                    ->select(array('f.id', 'f.field_name', 'f.admin_input_type'))
                    ->where('sf.set_id', $id)->find_many()
                );
        foreach($fields as &$field) {
            if ($field['admin_input_type'] === 'select' ||  $field['admin_input_type'] === 'multiselect') {
                $field['options'] = FCom_CustomField_Model_FieldOption::i()->getListAssocById($field['id']);
            }
        }

        BResponse::i()->json(array('id'=>$set->id, 'set_name'=>$set->set_name, 'fields'=>($fields)));
    }

    public function action_get_field()
    {
        $r = BRequest::i();
        $id = $r->get('id');
        $field = FCom_CustomField_Model_Field::i()->load($id);
        $options = FCom_CustomField_Model_FieldOption::i()->getListAssocById($field->id);
        BResponse::i()->json(array('id'=>$field->id, 'field_name'=>$field->field_name, 'admin_input_type'=>$field->admin_input_type, 'options'=>$options));
    }

    public function action_save__POST()
    {
         $data = BRequest::i()->post();
         $prodId = $data['id'];
         $json = $data['json'];

         $res = BDb::many_as_array(FCom_CustomField_Model_ProductField::i()->orm()->where('product_id',$prodId)->find_many());

         if(empty($res)) {
            $new = FCom_CustomField_Model_ProductField::i()->create();
            $new->product_id = $prodId;
            $new->_data_serialized = $json;
            $new->save();
            $status = 'Successfully saved.';
         } else {

            $row = FCom_CustomField_Model_ProductField::i()->load($res[0]['id']);
            $row->_data_serialized = $json;
            $row->save();
            $status = 'Successfully updated.';
         }

         BResponse::i()->json(array('status'=>$status));
    }
}
