<?php

class FCom_CustomAttr extends BClass
{
    public static function bootstrap()
    {
        switch (FCom::area()) {
            case 'FCom_Admin': FCom_CustomAttr_Admin::bootstrap(); break;
            case 'FCom_Frontend': FCom_CustomAttr_Frontend::bootstrap(); break;
        }
    }
}

class FCom_CustomAttr_Admin extends BClass
{
    public static function bootstrap()
    {
        BFrontController::i()
            ->route('GET /attrsets', 'FCom_CustomAttr_Admin_Controller_AttrSets.index')
            ->route('GET /attrsets/grid_data', 'FCom_CustomAttr_Admin_Controller_AttrSets.grid_data')
            ->route('POST /attrsets/grid_data', 'FCom_CustomAttr_Admin_Controller_AttrSets.grid_data_post')
            ->route('GET /attrsets/form/:id', 'FCom_CustomAttr_Admin_Controller_AttrSets.form')
            ->route('GET /attrsets/form_tab/:id', 'FCom_CustomAttr_Admin_Controller_AttrSets.form_tab')
            ->route('POST /attrsets/form/:id', 'FCom_CustomAttr_Admin_Controller_AttrSets.form_post')
        ;

        BLayout::i()
            ->allViews('Admin/views', 'customattr')
        ;

        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_CustomAttr_Admin::layout')
        ;
    }

    public static function layout()
    {
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'root', 'do'=>array(
                        array('addNav', 'catalog/attrsets', array('label'=>'Attribute Sets', 'href'=>BApp::url('FCom_CustomAttr', '/attrsets'))),
                    )),
                ),
                '/customattr/attrsets'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('customattr/attrsets')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/attrsets'))),
                ),
                '/customattr/attrsets/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('customattr/attrsets/form')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/attrsets'))),
                ),
            ));
    }
}

class FCom_CustomAttr_Frontend extends BClass
{
    public static function bootstrap()
    {

    }
}

class FCom_CustomAttr_Model_Set extends FCom_Core_Model_Abstract
{
    protected static $_table = 'a_attrset';
}

class FCom_CustomAttr_Model_Attribute extends FCom_Core_Model_Abstract
{
    protected static $_table = 'a_attribute';
}

class FCom_CustomAttr_Model_Product extends FCom_Core_Model_Abstract
{
    protected static $_table = 'a_product_custom';
}