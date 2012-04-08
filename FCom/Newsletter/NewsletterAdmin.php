<?php

class FCom_Newsletter_Admin extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()->addAllViews('Admin/views');
    }
}