<?php

class Sellvana_Sales_Model_Order_State_Custom extends Sellvana_Sales_Model_StateCustom_ConcreteAbstract
{
    protected static $_entityType = 'order';

    public function calcState()
    {
        //$itemStates = $this->getItemStateStatistics('custom');

        return $this;
    }
}
