<?php

class FCom_GoogleCheckout_Admin extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Admin/views');
    }
}