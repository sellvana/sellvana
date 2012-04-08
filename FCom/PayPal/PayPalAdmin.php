<?php

class FCom_PayPal_Admin extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Admin/views');
    }
}