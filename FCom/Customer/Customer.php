<?php

class FCom_Customer extends BClass
{
    public static function bootstrap()
    {
        switch (FCom::area()) {
            case 'FCom_Frontend': FCom_Customer_Frontend::bootstrap(); break;
            case 'FCom_Admin': FCom_Customer_Admin::bootstrap(); break;
        }
    }
}

class FCom_Customer_Frontend extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_Customer_Frontend::layout')
        ;

        BFrontController::i()
            ->route('GET /customers', 'FCom_Customer_Frontend_Controller.index')
        ;

        BLayout::i()->allViews('Frontend/views', 'customer');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            '/customer'=>array(

            ),
        ));
    }
}

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

        BLayout::i()->allViews('Admin/views', 'customer');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'root', 'do'=>array(
                    array('addNav', 'customers', array('label'=>'Customers', 'pos'=>300)),
                    array('addNav', 'customers/customer', array('label'=>'Customers',
                        'href'=>BApp::url('FCom_Customer', '/customers/customer'))),
                )),
            ),
        ));
    }
}