<?php

class FCom_Customer_Frontend extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_Customer_Frontend::layout')
        ;

        BFrontController::i()
            ->route('GET|POST /login', 'FCom_Customer_Frontend_Controller.login')
            ->route('GET|POST /customer/register', 'FCom_Customer_Frontend_Controller.register')
            ->route('GET|POST /customer/password/recover', 'FCom_Customer_Frontend_Controller.password_recover')
            ->route('GET|POST /customer/password/reset', 'FCom_Customer_Frontend_Controller.password_reset')
            ->route('GET /logout', 'FCom_Customer_Frontend_Controller.logout')

            ->route('GET /myaccount', 'FCom_Customer_Frontend_Controller_Account.index')
            ->route('GET|POST /myaccount/.action', 'FCom_Customer_Frontend_Controller_Account')
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
        ));
    }
}