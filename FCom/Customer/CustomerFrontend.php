<?php

class FCom_Customer_Frontend extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_Customer_Frontend::layout')
        ;

        BFrontController::i()
            ->route('GET /login', 'FCom_Customer_Frontend_Controller.login')
            ->route('POST /login', 'FCom_Customer_Frontend_Controller.login__POST')
            ->route('GET|POST /customer/register', 'FCom_Customer_Frontend_Controller.register')
            ->route('GET|POST /customer/password/recover', 'FCom_Customer_Frontend_Controller.password_recover')
            ->route('GET|POST /customer/password/reset', 'FCom_Customer_Frontend_Controller.password_reset')
            ->route('GET /logout', 'FCom_Customer_Frontend_Controller.logout')

            ->route('GET /myaccount', 'FCom_Customer_Frontend_Controller_Account.index')
            ->route('GET|POST /myaccount/.action', 'FCom_Customer_Frontend_Controller_Account')

            //addresses
            ->route('GET /customer/address/shipping', 'FCom_Customer_Frontend_Controller_Address.shipping')
            ->route('GET /customer/address/billing', 'FCom_Customer_Frontend_Controller_Address.billing')
            ->route('POST /customer/address', 'FCom_Customer_Frontend_Controller_Address.address_post')
        ;

        BLayout::i()->addAllViews('Frontend/views');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
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
            '/customer/address'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('customer/address')),
            ),
        ));
    }
}