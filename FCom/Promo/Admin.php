<?php

class FCom_Promo_Admin extends BClass
{
    static public function bootstrap()
    {
        FCom_Admin_Controller_MediaLibrary::i()->allowFolder('media/promo');
    }
}
