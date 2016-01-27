<?php

class Sellvana_Sales_Model_Order_Item_State_Custom extends Sellvana_Sales_Model_StateCustom_ConcreteAbstract
{
    protected static $_entityType = 'order_item';

    public function calcState()
    {
        return $this;
    }
}
