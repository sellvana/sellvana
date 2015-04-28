<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_State_Refund extends Sellvana_Sales_Model_Order_State_Abstract
{
    const NONE = 'none',
        PROCESSING = 'processing',
        PARTIAL = 'partial',
        REFUNDED = 'refunded';

    protected $_valueLabels = [
        self::NONE => 'None',
        self::PROCESSING => 'Processing',
        self::PARTIAL => 'Partial',
        self::REFUNDED => 'Refunded',
    ];

    protected $_defaultValue = self::NONE;

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

    public function setRefunded()
    {
        return $this->changeState(self::REFUNDED);
    }

    public function calcState()
    {
        $itemStates = $this->getItemStateStatistics('refund');

        if (!empty($itemStates[Sellvana_Sales_Model_Order_Item_State_Refund::PROCESSING])) {
            return $this->setProcessing();
        }
        if (!empty($itemStates[Sellvana_Sales_Model_Order_Item_State_Refund::PARTIAL])) {
            return $this->setPartial();
        }
        if (!empty($itemStates[Sellvana_Sales_Model_Order_Item_State_Refund::REFUNDED])) {
            return $this->setRefunded();
        }

        return $this;
    }
}
