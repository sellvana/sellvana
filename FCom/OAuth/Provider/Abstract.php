<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract class FCom_OAuth_Provider_Abstract extends BClass
{
    public function onAfterGetAccessToken($tokenModel)
    {
        // better to have everything in the same module, than two way module references
        if (BModuleRegistry::i()->isLoaded('FCom_Admin')) {
            $userId = $tokenModel->get('admin_id');
            $hlp = FCom_Customer_Model_Customer::i();
            if ($userId && !$hlp->isLoggedIn()) {
                $user = $hlp->load($userId)->login();
            }
        }

        if (BModuleRegistry::i()->isLoaded('FCom_Customer')) {
            $userId = $tokenModel->get('customer_id');
            $hlp = FCom_Customer_Model_Customer::i();
            if ($userId && !$hlp->isLoggedIn()) {
                $user = $hlp->load($userId)->login();
            }
        }
    }

}
