<?php

/**
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @project sellvana_core
 */
class Sellvana_Feedback_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/feedback' => BLocale::i()->_('Feedback Settings'),
        ]);
    }
}
