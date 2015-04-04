<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_ApiServer_Main
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class FCom_ApiServer_Main extends BClass
{
    public function bootstrap()
    {
        /*
        $this->BRouting
            ->route('GET|POST|PUT|DELETE /v1/customers/.action', 'Sellvana_Customer_ApiServer_Controller_Rest')
        ;
        */
        $this->FCom_Admin_Model_Role->createPermission([
            'apiserver' => 'Remote API Server',
        ]);
    }
}
