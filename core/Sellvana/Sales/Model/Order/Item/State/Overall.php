<?php defined('BUCKYBALL_ROOT_DIR') || die();

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

    protected $_setValueNotificationTemplates = [
        self::BACKORDERED => 'email/sales/order-item-state-overall-backordered',
        self::CANCELED => 'email/sales/order-item-state-overall-canceled',
    ];

    protected $_defaultValue = self::PENDING;

    public function setPending()
    {
        return $this->changestate(self::PENDING);
    }

    public function setBackordered()
    {
        return $this->changestate(self::BACKORDERED);
    }

    public function setProcessing()
    {
        return $this->changestate(self::PROCESSING);
    }

    public function setComplete()
    {
        return $this->changestate(self::COMPLETE);
    }

    public function setCanceled()
    {
        return $this->changestate(self::CANCELED);
    }

    public function calcState()
    {
        /** @var Sellvana_Sales_Model_Order_Item_State $context */
        $context = $this->getContext();

        /** @var Sellvana_Sales_Model_Order_Item $model */
        $model = $context->getModel();

        if ($model->get('qty_backordered') > 0) {
            return $this->setBackordered();
        }

        if ($context->cancel()->getValue() === Sellvana_Sales_Model_Order_Item_State_Cancel::CANCELED) {
            return $this->setCanceled();
        }

        if ($context->payment()->isComplete() && $context->delivery()->isComplete()) {
            return $this->setComplete();
        }

        if ($model->get('qty_shipped') || $model->get('qty_paid')) {
            return $this->setProcessing();
        }

        return $this;
    }
}
