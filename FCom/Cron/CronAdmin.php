<?php

class FCom_Cron_Admin extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()->addAllViews('Admin/views');
    }
}