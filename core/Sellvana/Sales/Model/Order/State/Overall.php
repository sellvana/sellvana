<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    const PENDING = 'pending',
        PLACED = 'placed',
        REVIEW = 'review',
        FRAUD = 'fraud',
        LEGIT = 'legit',
        PROCESSING = 'processing',
        COMPLETE = 'complete',
        CANCEL_REQUESTED = 'cancel_req',
        CANCELED = 'canceled',
        ARCHIVED = 'archived';

    protected $_valueLabels = [
        self::PENDING => 'Pending',
        self::PLACED => 'Placed',
        self::REVIEW => 'Review',
        self::FRAUD => 'Fraud',
        self::LEGIT => 'Passed Verification',
        self::PROCESSING => 'Processing',
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
}
