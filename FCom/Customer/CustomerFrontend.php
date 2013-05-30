<?php

class FCom_Customer_Frontend extends BClass
{
    public static function bootstrap()
    {
        BEvents::i()
            ->on('BLayout::theme.load.after', 'FCom_Customer_Frontend::layout')
        ;

        BRouting::i()
            ->any('/login', 'FCom_Customer_Frontend_Controller.login')
            ->any('/customer/register', 'FCom_Customer_Frontend_Controller.register')
            ->any('/customer/password/recover', 'FCom_Customer_Frontend_Controller.password_recover')
            ->any('/customer/password/reset', 'FCom_Customer_Frontend_Controller.password_reset')
            ->get('/logout', 'FCom_Customer_Frontend_Controller.logout')

            ->get('/customer/myaccount', 'FCom_Customer_Frontend_Controller_Account.index')
            ->any('/customer/myaccount/.action', 'FCom_Customer_Frontend_Controller_Account')

            //orders
            ->any('/customer/order', 'FCom_Customer_Frontend_Controller_Order.index')
            ->get('/customer/order/.action', 'FCom_Customer_Frontend_Controller_Order')

            //addresses
            ->any('/customer/address', 'FCom_Customer_Frontend_Controller_Address.index')
            ->get('/customer/address/.action', 'FCom_Customer_Frontend_Controller_Address')
            //->route('GET /customer/address/billing', 'FCom_Customer_Frontend_Controller_Address.billing')
        ;

        BEvents::i()->on('FCom_Checkout_Model_Cart::addProduct', 'FCom_Customer_Model_Customer::onAddProductToCart');

        BLayout::i()->addAllViews('Frontend/views');
#echo '*FCom_Customer_Frontend*';
    }

    public static function layout()
    {
        BLayout::i()->addLayout(array(
            '/customer/login'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/login')),
            ),
            '/customer/register'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/register')),
            ),
            '/customer/password/recover'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/password-recover')),
            ),
            '/customer/password/reset'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/password-reset')),
            ),
            '/customer/account'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/account')),
            ),
            '/customer/account/edit'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/account/edit')),
            ),
            '/customer/account/editpassword'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/account/editpassword')),
            ),
            '/customer/address/edit'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/address/edit')),
            ),
            '/customer/address/list'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/address/list')),
            ),
            '/customer/address/choose'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/address/choose')),
            ),
            '/customer/order/list'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/order/list')),
            ),
            '/customer/order/view'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/order/view')),
            ),
        ));
    }
}