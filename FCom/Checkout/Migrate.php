<?php
class FCom_Checkout_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.1', array($this, 'upgrade_0_1_1'));
    }

    public function install()
    {
        FCom_Checkout_Model_Cart::install();
        FCom_Checkout_Model_CartItem::install();
    }

    public function upgrade_0_1_1()
    {
        FCom_Checkout_Model_CartItem::upgrade_0_1_1();
    }
}
