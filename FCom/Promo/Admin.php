<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Promo_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Controller_MediaLibrary->allowFolder('media/promo');
        $this->FCom_Admin_Model_Role->createPermission([
            'promo' => 'Promotions',

        ]);
    }
}
