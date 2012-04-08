<?php

class FCom_Newsletter_Frontend extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()->allViews('Frontend/views');
    }
}