<?php

class FCom_Promo_Admin extends BClass
{
    static public function bootstrap()
    {
        FCom_Admin_Controller_MediaLibrary::i()->allowFolder( 'media/promo' );
        FCom_Admin_Model_Role::i()->createPermission( [
            'promo' => 'Promotions',

        ] );
    }
}
