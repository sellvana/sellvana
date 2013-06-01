<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Admin
    extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()
            ->addAllViews('Admin/views')
            ->loadLayoutAfterTheme('Admin/layout.yml');

        BRouting::i()
            ->get('/customer-groups', 'FCom_CustomerGroups_Admin_Controller_CustomerGroups.index') // list groups
            ->any('/customer-groups/.action', 'FCom_CustomerGroups_Admin_Controller_CustomerGroups') // add, edit groups
            ->any('/tier-prices/.action', 'FCom_CustomerGroups_Admin_Controller_TierPrices'); // add, edit TP

        FCom_Admin_Model_Role::i()->createPermission(
            array(
                'customer_groups' => "Customer Groups"
            )
        );
    }
}