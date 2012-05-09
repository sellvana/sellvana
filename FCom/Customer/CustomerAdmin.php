<?php

class FCom_Customer_Admin extends BClass
{
    public static function bootstrap()
    {
        BFrontController::i()
            ->route('GET /customers', 'FCom_Customer_Admin_Controller_Customers.index')
            ->route('GET|POST /customers/.action', 'FCom_Customer_Admin_Controller_Customers')
            ->route('GET|POST /customers/import/.action', 'FCom_Customer_Admin_Controller_CustomersImport')
        ;

        BLayout::i()->addAllViews('Admin/views')->afterTheme('FCom_Customer_Admin::layout');

        FCom_Admin_Model_Role::i()->createPermission(array(
            'api/customers' => 'Customers',
            'api/customers/view' => 'View',
            'api/customers/update' => 'Update',
            'customers' => 'Customers',
            'customers/manage' => 'Manage',
            'customers/import' => 'Import',
        ));

        FCom_Admin_Controller_MediaLibrary::i()->allowFolder('storage/import/customers');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'admin/header', 'do'=>array(
                    array('addNav', 'customer', array('label'=>'Customers', 'pos'=>300)),
                    array('addNav', 'customer/customers', array('label'=>'Customers',
                        'href'=>BApp::href('customers'))),
                    array('addNav', 'customer/import', array('label'=>'Import Customers',
                        'href'=>BApp::href('customers/import/index'))),
                )),
            ),

            '/customers'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('admin/grid')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'customer/customers'))),
            ),
            '/customers/form'=>array(
                array('layout', 'base'),
                array('layout', 'form'),
                array('hook', 'main', 'views'=>array('admin/form')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'customer/customers'))),
                array('view', 'admin/form', 'set'=>array(
                    'tab_view_prefix' => 'customer/customers-form/',
                ), 'do'=>array(
                    array('addTab', 'main', array('label'=>'Customer Info', 'pos'=>10)),
                )),
            ),
            '/customers/import'=>array(
                array('layout', 'base'),
                array('layout', 'form'),
                array('hook', 'main', 'views'=>array('customer/import')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'customer/import'))),
            ),
        ));
    }
}