<?php
class FCom_Checkout_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        FCom_Checkout_Model_Cart::install();
        FCom_Checkout_Model_CartItem::install();
    }
}
