<?php

class FCom_CustomField_Admin_Controller_Products extends FCom_Admin_Controller_Abstract
{
    public function fieldsetsGridConfig()
    {
        $config = array(
            'grid' => array(
                'id'      => 'fieldsets',
                'hiddengrid' => true,
                'caption' => 'Field Sets',
                'url'     => BApp::url('FCom_CustomField', '/fieldsets/grid_data'),
                'columns' => array(
                    'id' => array('label'=>'ID', 'width'=>55, 'sorttype'=>'number', 'key'=>true),
                    'set_code' => array('label'=>'Set Code', 'width'=>100, 'editable'=>true),
                    'set_name' => array('label'=>'Set Name', 'width'=>200, 'editable'=>true),
                ),
            ),
            'custom' => array('personalize'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
        );
        return $config;
    }

    public function fieldsGridConfig()
    {
        $config = FCom_CustomField_Admin_Controller_FieldSets::i()->fieldsGridConfig();
        $config['grid']['hiddengrid'] = true;
        return $config;
    }
}