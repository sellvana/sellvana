<?php

class FCom_CustomField extends BClass
{
    protected $_types;

    public static function bootstrap()
    {
        switch (FCom::area()) {
            case 'FCom_Admin': FCom_CustomField_Admin::bootstrap(); break;
            case 'FCom_Frontend': FCom_CustomField_Frontend::bootstrap(); break;
        }
    }


}

class FCom_CustomField_Admin extends BClass
{
    public static function bootstrap()
    {
        $ctrl = 'FCom_CustomField_Admin_Controller_FieldSets.';
        BFrontController::i()
            ->route('GET /fieldsets', $ctrl.'index')
            ->route('GET|POST /fieldsets/grid_data', $ctrl.'grid_data')
            ->route('GET|POST /fieldsets/set_field_grid_data', $ctrl.'set_field_grid_data')
            ->route('GET|POST /fieldsets/field_grid_data', $ctrl.'field_grid_data')
            ->route('GET|POST /fieldsets/field_option_grid_data', $ctrl.'field_option_grid_data')

            ->route('GET|POST /fieldsets/form/:id', $ctrl.'form')
            ->route('GET /fieldsets/form_tab/:id', $ctrl.'form_tab')
        ;

        BLayout::i()
            ->allViews('Admin/views', 'customfield')
        ;

        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_CustomField_Admin::layout')
        ;
    }

    public static function layout()
    {
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'root', 'do'=>array(
                        array('addNav', 'catalog/fieldsets', array('label'=>'Field Sets', 'href'=>BApp::url('FCom_CustomField', '/fieldsets'))),
                    )),
                ),
                '/customfield/fieldsets'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('customfield/fieldsets')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/fieldsets'))),
                ),
                '/customfield/fieldsets/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('customfield/fieldsets/form')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/fieldsets'))),
                ),
            ));
    }
}

class FCom_CustomField_Frontend extends BClass
{
    public static function bootstrap()
    {

    }
}

class FCom_CustomField_Model_Set extends FCom_Core_Model_Abstract
{
    protected static $_table = 'a_fieldset';
}

class FCom_CustomField_Model_Field extends FCom_Core_Model_Abstract
{
    protected static $_table = 'a_field';
}

class FCom_CustomField_Model_SetField extends FCom_Core_Model_Abstract
{
    protected static $_table = 'a_fieldset_field';
}

class FCom_CustomField_Model_FieldOption extends FCom_Core_Model_Abstract
{
    protected static $_table = 'a_field_option';
}

class FCom_CustomField_Model_ProductField extends FCom_Core_Model_Abstract
{
    protected static $_table = 'a_product_field';
}