<?php

class Sellvana_Sales_Model_Order_Item_State_Refund extends Sellvana_Sales_Model_Order_State_Abstract
{
    const NONE = 'none',
        PROCESSING = 'processing',
        PARTIAL = 'partial',
        REFUNDED = 'refunded';

    protected $_valueLabels = [
        self::NONE => (('None')),
        self::PROCESSING => (('Processing')),
        self::PARTIAL => (('Partial')),
        self::REFUNDED => (('Refunded')),
    ];

    protected $_defaultMethods = [
        self::NONE => 'setNone',
        self::PROCESSING => 'setProcessing',
        self::PARTIAL => 'setPartial',
        self::REFUNDED => 'setRefunded',
    ];

    protected $_defaultValue = self::NONE;

    protected $_defaultValueWorkflow = [
        self::NONE => [self::PROCESSING],
        self::PROCESSING => [self::REFUNDED, self::PARTIAL],
        self::PARTIAL => [self::REFUNDED],
        self::REFUNDED => [],
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

    public function setRefunded()
    {
        return $this->changeState(self::REFUNDED);
    }

    public function calcState()
    {
        /** @var Sellvana_Sales_Model_Order_Item $model */
        $model = $this->getContext()->getModel();

        if ($model->get('amount_refunded') >= ($model->get('row_total') - $model->get('row_discount'))) {
            return $this->setRefunded();
        }
        if ($model->get('amount_refunded') > 0) {
            return $this->setPartial();
        }

        return $this;
    }
}
