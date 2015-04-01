<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiSite_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_MultiSite_Admin extends BClass
{
    public function bootstrap()
    {
        $this->BRouting
            ->get('/multisite', 'Sellvana_MultiSite_Admin_Controller.index')
            ->any('/multisite/.action', 'Sellvana_MultiSite_Admin_Controller')
        ;

//        $this->BLayout
//            ->addAllViews('Admin/views')
//            ->loadLayoutAfterTheme('Admin/layout.yml')
//        ;
        $this->FCom_Admin_Model_Role->createPermission([
            'multi_site' => 'Multi Site'
        ]);
    }
}
