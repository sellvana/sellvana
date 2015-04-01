<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Customer_Main
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_Customer_Main extends BClass
{
    public function onOAuthAfterGetAccessToken($args)
    {
        $userId = $args['token_model']->get('customer_id');
        $hlp = $this->Sellvana_Customer_Model_Customer;
        if ($userId && !$hlp->isLoggedIn()) {
            $user = $hlp->load($userId)->login();
        }
    }
}