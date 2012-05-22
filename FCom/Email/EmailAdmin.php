<?php

class FCom_Email_Admin extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()->addAllViews('Admin/views');
    }
}