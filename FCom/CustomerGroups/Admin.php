<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Admin extends BClass
{
    public static function bootstrap()
    {
        FCom_Admin_Model_Role::i()->createPermission(array(
            'customer_groups' => "Customer Groups"
        ));
    }
}