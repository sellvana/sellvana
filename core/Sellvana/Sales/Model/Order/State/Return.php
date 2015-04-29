<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_State_Return extends Sellvana_Sales_Model_Order_State_Abstract
{
    const NONE = 'none',
        PROCESSING = 'processing',
        PARTIAL = 'partial',
        RETURNED = 'returned';

    protected $_valueLabels = [
        self::NONE => 'None',
        self::PROCESSING => 'Processing',
        self::PARTIAL => 'Partial',
        self::RETURNED => 'Returned',
    ];

    protected $_setValueNotificationTemplates = [
        self::RETURNED => 'email/sales/order-state-delivery-returned',
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

    public function setReturned()
    {
        return $this->changeState(self::RETURNED);
    }

    public function calcState()
    {
        $itemStates = $this->getItemStateStatistics('returns');

        if (!empty($itemStates[Sellvana_Sales_Model_Order_Item_State_Return::PROCESSING])) {
            return $this->setProcessing();
        }
        if (!empty($itemStates[Sellvana_Sales_Model_Order_Item_State_Return::PARTIAL])) {
            return $this->setPartial();
        }
        if (!empty($itemStates[Sellvana_Sales_Model_Order_Item_State_Return::RETURNED])) {
            return $this->setReturned();
        }

        return $this;
    }
}
