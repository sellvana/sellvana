<?php

class FCom_FreshBooks_Frontend extends BClass
{
    public static function bootstrap()
    {
        BEvents::i()->on('FCom_Sales_Model_Order::invoice', 'FCom_FreshBooks.createInvoiceFromOrder');

    }

}
