<?php

require_once __DIR__.'/Sales.php';

class FCom_Sales_Frontend extends BClass
{
    public static function bootstrap()
    {
        BRouting::i()
            //api route for sales orders
            ->route( 'GET|POST /v1/sales/order', 'FCom_Sales_ApiServer_V1_Order.index')
            ->route( 'GET|POST|DELETE|PUT /v1/sales/order/:id', 'FCom_Sales_ApiServer_V1_Order.index');
    }
}