<?php

class FCom_Sales_Admin extends BClass
{
    public static function bootstrap()
    {
        BFrontController::i()
            ->route('GET /orders', 'FCom_Sales_Admin_Controller_Orders.index')
            ->route('GET|POST /orders/.action', 'FCom_Sales_Admin_Controller_Orders')
        ;

        BLayout::i()->addAllViews('Admin/views')->afterTheme('FCom_Sales_Admin::layout');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'admin/header', 'do'=>array(
                    array('addNav', 'order', array('label'=>'Orders', 'pos'=>300)),
                    array('addNav', 'order/orders', array('label'=>'Orders',
                        'href'=>BApp::href('orders'))),
                )),
            ),

            '/orders'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('admin/grid')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'order/orders'))),
            ),
            '/orders/form'=>array(
                array('layout', 'base'),
                array('layout', 'form'),
                array('hook', 'main', 'views'=>array('admin/form')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'order/orders'))),
                array('view', 'admin/form', 'set'=>array(
                    'tab_view_prefix' => 'order/orders-form/',
                ), 'do'=>array(
                    array('addTab', 'main', array('label'=>'Order Info', 'pos'=>10)),
                )),
            ),
        ));
    }
}