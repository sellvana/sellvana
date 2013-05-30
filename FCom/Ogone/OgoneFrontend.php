<?php

require_once __DIR__.'/Ogone.php';

class FCom_Ogone_Frontend extends BClass
{
    static public function bootstrap()
    {
        FCom_Ogone::i()->bootstrap();

        BLayout::i()->addAllViews('Frontend/views');
    }
}