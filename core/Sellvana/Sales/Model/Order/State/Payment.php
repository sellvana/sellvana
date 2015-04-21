<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_State_Payment extends Sellvana_Sales_Model_Order_State_Abstract
{
    const FREE = 'free',
        UNPAID = 'unpaid',
        PROCESSING = 'processing',
        PARTIAL_PAID = 'partial_paid',
        PAID = 'paid',
        OUTSTANDING = 'outstanding',
        VOID = 'void';

    protected $_valueLabels = [
        self::FREE => 'Free',
        self::UNPAID => 'Unpaid',
        self::PROCESSING => 'Processing',
        self::PARTIAL_PAID => 'Partial Paid',
        self::PAID => 'Paid',
        self::OUTSTANDING => 'Outstanding',
        self::VOID => 'Void',
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

    public function setVoid()
    {
        return $this->changeState(self::VOID);
    }
}
