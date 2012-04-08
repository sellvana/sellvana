<?php

class FCom_Customer_Admin extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_Customer_Admin::layout')
        ;

        BFrontController::i()
            ->route('GET /customers', 'FCom_Customer_Admin_Controller.index')
        ;

        BLayout::i()->addAllViews('Admin/views');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'root', 'do'=>array(
                    array('addNav', 'customers', array('label'=>'Customers', 'pos'=>300)),
                    array('addNav', 'customers/customer', array('label'=>'Customers',
                        'href'=>BApp::href('customers/customer'))),
                )),
            ),
        ));
    }
}