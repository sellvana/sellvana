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
}
