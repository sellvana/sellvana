<?php

class FCom_Geo extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()->addAllViews('views');
    }

    public static function migrate()
    {
        BMigrate::install('0.1.0', function() {
            FCom_Geo_Model_Country::i()->install();
            FCom_Geo_Model_Region::i()->install();
        });
    }
}