<?php

require_once __DIR__.'/Sales.php';

class FCom_Sales_Api extends BClass
{
    public static function bootstrap()
    {
        BRouting::i()
            //api route for sales orders
            ->any('/v1/sales/order', 'FCom_Sales_ApiServer_V1_Order.index')
            ->any('/v1/sales/order/:id', 'FCom_Sales_ApiServer_V1_Order.index');
    }
}