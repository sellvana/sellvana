<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_OAuth_Provider_Abstract
 *
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 */
abstract class FCom_OAuth_Provider_Abstract extends BClass
{
    public function onAfterGetAccessToken($tokenModel)
    {
        // better to have everything in the same module, than two way module references
        if ($this->BModuleRegistry->isLoaded('FCom_Admin')) {
            $userId = $tokenModel->get('admin_id');
            $hlp = $this->FCom_Customer_Model_Customer;
            if ($userId && !$hlp->isLoggedIn()) {
                $user = $hlp->load($userId)->login();
            }
        }

        if ($this->BModuleRegistry->isLoaded('FCom_Customer')) {
            $userId = $tokenModel->get('customer_id');
            $hlp = $this->FCom_Customer_Model_Customer;
            if ($userId && !$hlp->isLoggedIn()) {
                $user = $hlp->load($userId)->login();
            }
        }
    }

}
