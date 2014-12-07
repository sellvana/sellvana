<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_State_Payment extends FCom_Core_Model_Abstract_State_Concrete
{
    const FREE = 'free',
        UNPAID = 'unpaid',
        PROCESSING = 'processing',
        PARTIAL = 'partial',
        PAID = 'paid',
        OUTSTANDING = 'outstanding',
        CANCELED = 'canceled',
        REFUNDED = 'refunded';

    protected $_valueLabels = [
        self::FREE => 'Free',
        self::UNPAID => 'Unpaid',
        self::PROCESSING => 'Processing',
        self::PARTIAL => 'Partial',
        self::PAID => 'Paid',
        self::OUTSTANDING => 'Outstanding',
        self::CANCELED => 'Canceled',
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

    public function setPartial()
    {
        return $this->changeState(self::PARTIAL);
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

    public function setRefunded()
    {
        return $this->changeState(self::REFUNDED);
    }
}
