<?php

class FCom_Sales_Admin extends BClass
{
    public static function bootstrap()
    {
        FCom_Sales_Main::bootstrap();
        
        BRouting::i()
            ->get('/orders', 'FCom_Sales_Admin_Controller_Orders.index')
            ->any('/orders/.action', 'FCom_Sales_Admin_Controller_Orders')

            ->get('/orderstatus', 'FCom_Sales_Admin_Controller_OrderStatus.index')
            ->any('/orderstatus/.action', 'FCom_Sales_Admin_Controller_OrderStatus')
        ;

        BLayout::i()->addAllViews('Admin/views')
            ->loadLayoutAfterTheme('Admin/layout.yml');
    }

}