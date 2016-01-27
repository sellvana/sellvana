<?php

/**
 * Class FCom_Cron_Admin
 *
 * @property FCom_Cron_Model_Task $FCom_Cron_Model_Task
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */

class FCom_Cron_Admin extends BClass
{

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/FCom_Cron' => BLocale::i()->_('Cron Settings'),
        ]);
    }
}
