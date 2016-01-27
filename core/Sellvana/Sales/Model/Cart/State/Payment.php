<?php

class Sellvana_Sales_Model_Cart_State_Payment extends FCom_Core_Model_Abstract_State_Concrete
{
    const FREE = 'free',
        UNPAID = 'unpaid',
        PAID = 'paid', // ex. store_credit
        EXTERNAL = 'external',
        ACCEPTED = 'accepted',
        FAILED = 'failed';

    protected $_valueLabels = [
        self::FREE => 'Free',
        self::UNPAID => 'Unpaid',
        self::PAID => 'Paid',
        self::EXTERNAL => 'External',
        self::ACCEPTED => 'Accepted',
        self::FAILED => 'Failed',
    ];

    protected $_defaultValue = self::UNPAID;

    public function setFree()
    {
        return $this->changeState(self::FREE);
    }

    public function setUnpaid()
    {
        return $this->changeState(self::UNPAID);
    }

    public function setPaid()
    {
        return $this->changeState(self::PAID);
    }

    public function setExternal()
    {
        return $this->changeState(self::EXTERNAL);
    }

    public function setAccepted()
    {
        return $this->changeState(self::ACCEPTED);
    }

    public function setFailed()
    {
        return $this->changeState(self::FAILED);
    }
}
