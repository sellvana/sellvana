<?php
class FCom_Sales_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        FCom_Sales_Model_Order::install();
        FCom_Sales_Model_OrderItem::install();
    }
}
