<?php

class FCom_MultiSite_Admin extends BClass
{
    static public function bootstrap()
    {
        BRouting::i()
            ->get('/multisite', 'FCom_MultiSite_Admin_Controller.index')
            ->any('/multisite/.action', 'FCom_MultiSite_Admin_Controller')
        ;

        BLayout::i()
            ->addAllViews('Admin/views')
            ->loadLayoutAfterTheme('Admin/layout.yml')
        ;
    }
}
