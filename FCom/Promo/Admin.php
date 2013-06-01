<?php

class FCom_Promo_Admin extends BClass
{
    static public function bootstrap()
    {
        BRouting::i()

            ->route('GET /promo/vendors/autocomplete', 'Denteva_Admin_Controller_Vendors.autocomplete')

            ->route('GET /promo', 'FCom_Promo_Admin_Controller.index')
            ->route('GET|POST /promo/.action', 'FCom_Promo_Admin_Controller')

            ->route('GET /promo/form/:id/products', 'FCom_Promo_Admin_Controller.form_products')
            ->route('GET /promo/form/:id/group', 'FCom_Promo_Admin_Controller.form_group')
            ->route('GET /promo/attachments', 'FCom_Promo_Admin_Controller.attachments')
            ->route('GET /promo/attachments/download', 'FCom_Promo_Admin_Controller.attachments_download')
            ->route('POST /promo/attachments/:do', 'FCom_Promo_Admin_Controller.attachments_post')
        ;

        BLayout::i()
            ->allViews('Admin/views')
            ->loadLayoutAfterTheme('Admin/layout.yml')
        ;

        FCom_Admin_Controller_MediaLibrary::i()->allowFolder('media/promo');
    }
}