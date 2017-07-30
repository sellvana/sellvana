<?php

class Sellvana_Sales_Model_Order_Item_State_Cancel extends Sellvana_Sales_Model_Order_State_Abstract
{
    const NONE = 'none',
        REQUESTED = 'requested',
        PROCESSING = 'processing',
        PARTIAL = 'partial',
        CANCELED = 'canceled';

    protected $_valueLabels = [
        self::NONE => (('None')),
        self::REQUESTED => (('Requested')),
        self::PROCESSING => (('Processing')),
        self::PARTIAL => (('Partial')),
        self::CANCELED => (('Canceled')),
    ];

    protected $_defaultValue = self::NONE;

    protected $_defaultMethods = [
        self::NONE => 'setNone',
        self::REQUESTED => 'setRequested',
        self::PROCESSING => 'setProcessing',
        self::PARTIAL => 'setPartial',
        self::CANCELED => 'setCanceled',
    ];

    protected $_defaultValueWorkflow = [
        self::NONE => [self::REQUESTED, self::PROCESSING],
        self::REQUESTED => [self::PROCESSING],
        self::PROCESSING => [self::PARTIAL, self::CANCELED],
        self::PARTIAL => [self::CANCELED],
        self::CANCELED => [],
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

    public function setCanceled()
    {
        return $this->changeState(self::CANCELED);
    }

    public function calcState()
    {
        /** @var Sellvana_Sales_Model_Order_Item $model */
        $model = $this->getContext()->getModel();

        if ($model->get('qty_canceled') == $model->get('qty_ordered')) {
            return $this->setCanceled();
        }
        if ($model->get('qty_canceled') > 0) {
            return $this->setPartial();
        }

        return $this;
    }
}
