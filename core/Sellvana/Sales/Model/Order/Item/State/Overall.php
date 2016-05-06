<?php

class Sellvana_Sales_Model_Order_Item_State_Overall extends Sellvana_Sales_Model_Order_State_Abstract
{
    const PENDING = 'pending',
        BACKORDERED = 'backordered',
        PROCESSING = 'processing',
        COMPLETE = 'complete',
        CANCELED = 'canceled';

    protected $_valueLabels = [
        self::PENDING => 'Pending',
        self::BACKORDERED => 'Backordered',
        self::PROCESSING => 'Processing',
        self::COMPLETE => 'Complete',
        self::CANCELED => 'Canceled',
    ];

    protected $_defaultMethods = [
        self::PENDING => 'setPending',
        self::BACKORDERED => 'setBackordered',
        self::PROCESSING => 'setProcessing',
        self::COMPLETE => 'setComplete',
        self::CANCELED => 'setCanceled',
    ];

    protected $_setValueNotificationTemplates = [
        self::BACKORDERED => 'email/sales/order-item-state-overall-backordered',
        self::CANCELED => 'email/sales/order-item-state-overall-canceled',
    ];

    protected $_defaultValue = self::PENDING;

    public function setPending()
    {
        return $this->changeState(self::PENDING);
    }

    public function setBackordered()
    {
        return $this->changeState(self::BACKORDERED);
    }

    public function setProcessing()
    {
        return $this->changeState(self::PROCESSING);
    }

    public function setComplete()
    {
        return $this->changeState(self::COMPLETE);
    }

    public function setCanceled()
    {
        return $this->changeState(self::CANCELED);
    }

    public function calcState()
    {
        /** @var Sellvana_Sales_Model_Order_Item_State $context */
        $context = $this->getContext();

        /** @var Sellvana_Sales_Model_Order_Item $item */
        $item = $context->getModel();

        if ($item->get('qty_backordered') > 0) {
            return $this->setBackordered();
        }

        if ($context->cancel()->getValue() === Sellvana_Sales_Model_Order_Item_State_Cancel::CANCELED) {
            return $this->setCanceled();
        }

        if ($context->payment()->isComplete() && $context->delivery()->isComplete()) {
            return $this->setComplete();
        }

        if ($item->get('qty_shipped') > 0 || $item->get('qty_paid') > 0) {
            return $this->setProcessing();
        }

        return $this;
    }
}
