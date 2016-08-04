<?php

class Sellvana_Sales_Model_Order_State_Overall extends Sellvana_Sales_Model_Order_State_Abstract
{
    const PENDING = 'pending',
        PLACED = 'placed',
        REVIEW = 'review',
        FRAUD = 'fraud',
        LEGIT = 'legit',
        PROCESSING = 'processing',
        BACKORDERED = 'backordered',
        COMPLETE = 'complete',
        CANCEL_REQUESTED = 'cancel_req',
        CANCELED = 'canceled',
        ARCHIVED = 'archived';

    protected static $_origClass = __CLASS__;

    protected $_valueLabels = [
        self::PENDING => 'Pending',
        self::PLACED => 'Placed',
        self::REVIEW => 'Review',
        self::FRAUD => 'Fraud',
        self::LEGIT => 'Passed Verification',
        self::PROCESSING => 'Processing',
        self::BACKORDERED => 'Backordered',
        self::COMPLETE => 'Complete',
        self::CANCEL_REQUESTED => 'Cancel Requested',
        self::CANCELED => 'Canceled',
        self::ARCHIVED => 'Archived',
    ];

    protected $_setValueNotificationTemplates =[
        self::PLACED => [
            'email/sales/order-state-overall-placed',
            'email/sales/order-state-overall-placed-admin',
        ],
        self::REVIEW => 'email/sales/order-state-overall-review',
        self::FRAUD => 'email/sales/order-state-overall-fraud',
        self::LEGIT => 'email/sales/order-state-overall-legit',
        self::CANCEL_REQUESTED => 'email/sales/order-state-overall-cancel_req-admin',
        self::CANCELED => 'email/sales/order-state-overall-canceled',
    ];

    protected $_defaultValue = self::PENDING;

    protected $_defaultValueWorkflow = [
        self::PENDING => [self::PLACED],
        self::PLACED => [self::REVIEW, self::PROCESSING, self::BACKORDERED],
        self::REVIEW => [self::FRAUD, self::LEGIT],
        self::FRAUD => [self::CANCELED, self::ARCHIVED],
        self::LEGIT => [self::PROCESSING],
        self::PROCESSING => [self::BACKORDERED, self::COMPLETE, self::CANCEL_REQUESTED],
        self::BACKORDERED => [self::PROCESSING, self::COMPLETE],
        self::COMPLETE => [self::ARCHIVED],
        self::CANCEL_REQUESTED => [self::CANCELED],
        self::CANCELED => [self::ARCHIVED],
        self::ARCHIVED => [],
    ];

    public function setPending()
    {
        return $this->changeState(self::PENDING);
    }

    public function setPlaced()
    {
        return $this->changeState(self::PLACED);
    }

    public function setReview()
    {
        return $this->changeState(self::REVIEW);
    }

    public function setLegit()
    {
        return $this->changeState(self::LEGIT);
    }

    public function setFraud()
    {
        return $this->changeState(self::FRAUD);
    }

    public function setProcessing()
    {
        return $this->changeState(self::PROCESSING);
    }

    public function setBackordered()
    {
        return $this->changestate(self::BACKORDERED);
    }

    public function setComplete()
    {
        return $this->changeState(self::COMPLETE);
    }

    public function setCancelRequested()
    {
        return $this->changeState(self::CANCEL_REQUESTED);
    }

    public function setCanceled()
    {
        return $this->changeState(self::CANCELED);
    }

    public function setArchived()
    {
        return $this->changeState(self::ARCHIVED);
    }

    public function calcState()
    {
        /** @var Sellvana_Sales_Model_Order_State $context */
        $context = $this->getContext();

        $payment = $context->payment();
        $delivery = $context->delivery();
        $cancel = $context->cancel();

        /** @var Sellvana_Sales_Model_Order $model */
        $model = $this->getContext()->getModel();

        if ($model->get('qty_backordered') > 0) {
            $this->setBackordered();
            return $this;
        }

        if ($cancel->getValue() === Sellvana_Sales_Model_Order_Item_State_Cancel::CANCELED) {
            $this->setCanceled();
            return $this;
        }

        if ($payment->isComplete() && $delivery->isComplete()) {
            $this->setComplete();
            return $this;
        }

        if ($model->get('qty_shipped') || $model->get('amount_paid')) {
            $this->setProcessing();
            return $this;
        }

        return $this;
    }
}
