<?php

class Sellvana_Sales_Model_Order_State_Cancel extends Sellvana_Sales_Model_Order_State_Abstract
{
    const NONE = 'none',
        PROCESSING = 'processing',
        PARTIAL = 'partial',
        CANCELED = 'canceled';

    protected $_valueLabels = [
        self::NONE => 'None',
        self::PROCESSING => 'Processing',
        self::PARTIAL => 'Partial',
        self::CANCELED => 'Canceled',
    ];

    protected $_defaultValue = self::NONE;

    protected $_defaultValueWorkflow = [
        self::NONE => [self::PROCESSING],
        self::PROCESSING => [self::PARTIAL, self::CANCELED],
        self::PARTIAL => [self::CANCELED],
        self::CANCELED => [],
    ];

    public function setNone()
    {
        return $this->changeState(self::NONE);
    }

    public function setProcessing()
    {
        return $this->changeState(self::PROCESSING);
    }

    public function setPartial()
    {
        return $this->changeState(self::PARTIAL);
    }

    public function setCanceled()
    {
        return $this->changeState(self::CANCELED);
    }

    public function calcState()
    {
        $itemStates = $this->getItemStateStatistics('cancel');

        if (!empty($itemStates[Sellvana_Sales_Model_Order_Item_State_Cancel::PROCESSING])) {
            return $this->setProcessing();
        }
        if (!empty($itemStates[Sellvana_Sales_Model_Order_Item_State_Cancel::PARTIAL])) {
            return $this->setPartial();
        }
        if (!empty($itemStates[Sellvana_Sales_Model_Order_Item_State_Cancel::CANCELED])) {
            return $this->setCanceled();
        }

        return $this;
    }
}
