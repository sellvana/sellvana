<?php

class FCom_Ogone_Admin extends BClass
{
    static public function bootstrap()
    {
        FCom_Ogone_Main::i()->bootstrap();

        BLayout::i()->addAllViews('Admin/views');
    }
}