<?php

class FCom_Newsletter extends BClass
{
    public static function bootstrap()
    {
        switch (FCom::area()) {
            case 'FCom_Frontend': self::frontend(); break;
            case 'FCom_Admin': self::admin(); break;
        }
    }

    public static function frontend()
    {
        BLayout::i()->allViews('views_frontend', 'newsletter/');
    }

    public static function admin()
    {
        BLayout::i()->allViews('views_admin', 'newsletter/');
    }
}