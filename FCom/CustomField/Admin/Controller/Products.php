<?php

class FCom_CustomField_Admin_Controller_Products extends FCom_Admin_Controller_Abstract
{
    public function fieldsetsGridConfig()
    {
        $config = array(
            'grid' => array(
                'id'      => 'product_fieldsets',
                'hiddengrid' => true,
                'caption' => 'Field Sets',
                'url'     => BApp::url('FCom_CustomField', '/customfields/fieldsets/grid_data'),
                'columns' => array(
                    'id' => array('label'=>'ID', 'width'=>55, 'sorttype'=>'number', 'key'=>true),
                    'set_code' => array('label'=>'Set Code', 'width'=>100, 'editable'=>true),
                    'set_name' => array('label'=>'Set Name', 'width'=>200, 'editable'=>true),
                    'num_fields' => array('label' => 'Fields', 'width'=>30),
                ),
                'multiselect' => true,
            ),
            'custom' => array('personalize'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
            array('navButtonAdd', 'caption'=>'Add', 'buttonicon'=>'ui-icon-plus', 'position'=>'first',
                'title'=>'Add field sets to product', 'onClickButton'=>'function() { return addCustomFieldSets.call(this) }'),
        );
        return $config;
    }

    public function fieldsGridConfig()
    {
        $config = FCom_CustomField_Admin_Controller_FieldSets::i()->fieldsGridConfig();
        $config['grid']['id'] = 'product_fields';
        $config['grid']['hiddengrid'] = true;
        $config[] = array('navButtonAdd', 'caption'=>'Add', 'buttonicon'=>'ui-icon-plus', 'position'=>'first',
            'title'=>'Add fields to product', 'onClickButton'=>'function() { return addCustomFields.call(this) }');
        return $config;
    }

    public function action_fields_partial()
    {
        $id = BRequest::i()->params('id');
        $p = FCom_Catalog_Model_Product::i()->load($id);

        $fields = FCom_CustomField_Model_ProductField::i()->productFields($p, BRequest::i()->request());

        $view = $this->view('customfields/products/fields-partial');
        $view->set('model', $p)->set('fields', $fields);
        BLayout::i()->rootView('customfields/products/fields-partial');
        BResponse::i()->render();
    }
}