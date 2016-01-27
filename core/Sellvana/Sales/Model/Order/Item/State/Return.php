<?php

class Sellvana_Sales_Model_Order_Item_State_Return extends Sellvana_Sales_Model_Order_State_Abstract
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

    protected $_setValueNotificationTemplates =[
        self::RETURNED => 'email/sales/order-item-state-return-returned',
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
        /** @var Sellvana_Sales_Model_Order_Item $model */
        $model = $this->getContext()->getModel();

        if ($model->get('qty_returned') == $model->get('qty_ordered')) {
            return $this->setReturned();
        }
        if ($model->get('qty_returned') > 0) {
            return $this->setPartial();
        }

        return $this;
    }
}
