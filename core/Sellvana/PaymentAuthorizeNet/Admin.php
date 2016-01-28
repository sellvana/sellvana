<?php

/**
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_PaymentAuthorizeNet_Admin extends BClass {
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'authorize_net'          => BLocale::i()->_('Authorize Net'),
            'settings/Sellvana_PaymentAuthorizeNet' => BLocale::i()->_('Authorize Net Settings'),
        ]);
    }
}
