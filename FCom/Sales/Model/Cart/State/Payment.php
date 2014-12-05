<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Cart_State_Payment extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'free' => 'Free',
        'unpaid' => 'Unpaid',
        'external' => 'External',
        'paid' => 'Paid',
        'failed' => 'Failed',
    ];

    public function setFree()
    {
        return $this->changeState('free');
    }

    public function setUnpaid()
    {
        return $this->changeState('unpaid');
    }

    public function setExternal()
    {
        return $this->changeState('external');
    }

    public function setPaid()
    {
        return $this->changeState('paid');
    }

    public function setFailed()
    {
        return $this->changeState('failed');
    }
}
