<?php

class FCom_Test_Admin extends BClass
{
    /**
     * Bootstrap routes, events and layout for Admin part
     */
    static public function bootstrap()
    {
        BRouting::i()
            ->get('/tests/index', 'FCom_Test_Admin_Controller_Tests.index')
            ->get('/tests/run', 'FCom_Test_Admin_Controller_Tests.run')
            ->get('/tests/run2', 'FCom_Test_Admin_Controller_Tests.run2');

        BLayout::i()->addAllViews('Admin/views')->loadLayoutAfterTheme('Admin/layout.yml');
    }
}