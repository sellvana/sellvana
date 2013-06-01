<?php

class FCom_Translation_Admin extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Admin/views')
            ->loadLayoutAfterTheme('Admin/layout.yml');

        BRouting::i()
            ->get('/translations', 'FCom_Translation_Admin_Controller.index')
            ->any('/translations/.action', 'FCom_Translation_Admin_Controller')
        ;
    }
}