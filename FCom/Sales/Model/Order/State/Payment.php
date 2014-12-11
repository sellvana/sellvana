<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_State_Payment extends FCom_Core_Model_Abstract_State_Concrete
{
    const FREE = 'free',
        UNPAID = 'unpaid',
        PROCESSING = 'processing',
        PARTIAL_PAID = 'partial_paid',
        PAID = 'paid',
        OUTSTANDING = 'outstanding',
        CANCELED = 'canceled',
        PARTIAL_REFUNDED = 'partial_refunded',
        REFUNDED = 'refunded';

    protected $_valueLabels = [
        self::FREE => 'Free',
        self::UNPAID => 'Unpaid',
        self::PROCESSING => 'Processing',
        self::PARTIAL_PAID => 'Partial Paid',
        self::PAID => 'Paid',
        self::OUTSTANDING => 'Outstanding',
        self::CANCELED => 'Canceled',
        self::PARTIAL_REFUNDED => 'Partial Refunded',
        self::REFUNDED => 'Refunded',
    ];

    public function setFree()
    {
        return $this->changeState(self::FREE);
    }

    public function setUnpaid()
    {
        return $this->changeState(self::UNPAID);
    }

    public function setProcessing()
    {
        return $this->changeState(self::PROCESSING);
    }

    public function setPartialPaid()
    {
        return $this->changeState(self::PARTIAL_PAID);
    }

    public function setPaid()
    {
        return $this->changeState(self::PAID);
    }

    public function setOutstanding()
    {
        return $this->changeState(self::OUTSTANDING);
    }

    public function setCanceled()
    {
        return $this->changeState(self::CANCELED);
    }

    public function setPartialRefunded()
    {
        return $this->changeState(self::PARTIAL_REFUNDED);
    }

    public function setRefunded()
    {
        return $this->changeState(self::REFUNDED);
    }
}
