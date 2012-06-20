<?php

class FCom_CustomField_Frontend extends BClass
{
    public static function bootstrap()
    {
        FCom_CustomField_Common::bootstrap();

        BLayout::i()->addAllViews('Frontend/views');
    }
}