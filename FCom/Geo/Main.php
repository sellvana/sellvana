<?php

class FCom_Geo_Main extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()->addAllViews('views');
    }
}