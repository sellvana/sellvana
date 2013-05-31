<?php

class FCom_Customer_Api extends BClass
{
    public static function bootstrap()
    {
        BRouting::i()
            //api route for customer
            ->any('/v1/customer/customer', 'FCom_Customer_ApiServer_V1_Customer.index')
            ->any('/v1/customer/customer/:id', 'FCom_Customer_ApiServer_V1_Customer.index')

            //api route for customer address
            ->any('/v1/customer/address', 'FCom_Customer_ApiServer_V1_Address.index')
            ->any('/v1/customer/address/:id', 'FCom_Customer_ApiServer_V1_Address.index')
        ;
    }
}