<?php

class Sellvana_Sales_Model_Order_Item_State_Return extends Sellvana_Sales_Model_Order_State_Abstract
{
    const NONE = 'none',
        REQUESTED = 'requested',
        PROCESSING = 'processing',
        PARTIAL = 'partial',
        RETURNED = 'returned';

    protected $_valueLabels = [
        self::NONE => (('None')),
        self::REQUESTED => (('Requested')),
        self::PROCESSING => (('Processing')),
        self::PARTIAL => (('Partial')),
        self::RETURNED => (('Returned')),
    ];

    protected $_defaultMethods = [
        self::NONE => 'setNone',
        self::REQUESTED => 'setRequested',
        self::PROCESSING => 'setProcessing',
        self::PARTIAL => 'setPartial',
        self::RETURNED => 'setReturned',
    ];

    protected $_setValueNotificationTemplates =[
        self::RETURNED => 'email/sales/order-item-state-return-returned',
    ];

    protected $_defaultValue = self::NONE;

    protected $_defaultValueWorkflow = [
        self::NONE => [self::REQUESTED, self::PROCESSING],
        self::REQUESTED => [self::PROCESSING],
        self::PROCESSING => [self::RETURNED, self::PARTIAL],
        self::PARTIAL => [self::RETURNED],
        self::RETURNED => [],
    ];

    public function setNone()
    {
        return $this->changeState(self::NONE);
    }

    public function setRequested()
    {
        return $this->changeState(self::REQUESTED);
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
