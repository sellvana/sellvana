<?php

class FCom_MultiSite_Admin extends BClass
{
    static public function bootstrap()
    {
        BRouting::i()
            ->get( '/multisite', 'FCom_MultiSite_Admin_Controller.index' )
            ->any( '/multisite/.action', 'FCom_MultiSite_Admin_Controller' )
        ;

//        BLayout::i()
//            ->addAllViews('Admin/views')
//            ->loadLayoutAfterTheme('Admin/layout.yml')
//        ;
        FCom_Admin_Model_Role::i()->createPermission( array(
            'multi_site' => 'Multi Site'
        ) );
    }
}
