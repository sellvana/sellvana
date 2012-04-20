<?php

class FCom_Customer_Admin extends BClass
{
    public static function bootstrap()
    {
        BFrontController::i()
            ->route('GET /customers', 'FCom_Customer_Admin_Controller_Customers.index')
            ->route('GET|POST /customers/.action', 'FCom_Customer_Admin_Controller_Customers')
        ;

        BLayout::i()->addAllViews('Admin/views')->afterTheme('FCom_Customer_Admin::layout');

        FCom_Admin_Model_Role::i()->createPermission(array(
            'customers' => 'Customers',
        ));
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'root', 'do'=>array(
                    array('addNav', 'customer', array('label'=>'Customers', 'pos'=>300)),
                    array('addNav', 'customer/customers', array('label'=>'Customers',
                        'href'=>BApp::href('customers'))),
                )),
            ),

            '/customers'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/customers')),
                array('view', 'root', 'do'=>array(array('setNav', 'customer/customers'))),
            ),
            '/customers/form'=>array(
                array('layout', 'base'),
                array('layout', 'form'),
                array('hook', 'main', 'views'=>array('customer/customers-form')),
                array('view', 'root', 'do'=>array(array('setNav', 'customer/customers'))),
                array('view', 'customer/customers-form', 'set'=>array(
                    'tab_view_prefix' => 'customer/customers-form/',
                ), 'do'=>array(
                    array('addTab', 'main', array('label'=>'Customer Info', 'pos'=>10)),
                    array('addTab', 'addresses', array('label'=>'Addresses', 'async'=>true, 'pos'=>20)),
                )),
            ),
        ));
    }
}