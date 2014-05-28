<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ApiServer_Main extends BClass
{
    public static function bootstrap()
    {
        /*
        BRouting::i()
            ->route('GET|POST|PUT|DELETE /v1/customers/.action', 'FCom_Customer_ApiServer_Controller_Rest')
        ;
        */
        FCom_Admin_Model_Role::i()->createPermission([
            'apiserver' => 'Remote API Server',
        ]);
    }
}
