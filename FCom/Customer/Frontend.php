<?php

class FCom_Customer_Frontend extends BClass
{
    public static function bootstrap()
    {
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

        BLayout::i()->addAllViews('Frontend/views')
            ->loadLayoutAfterTheme('Frontend/layout.yml')
        ;
    }
}