<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_State_Payment extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'free' => 'Free',
        'unpaid' => 'Unpaid',
        'processing' => 'Processing',
        'partial' => 'Partial',
        'paid' => 'Paid',
        'outstanding' => 'Outstanding',
        'canceled' => 'Canceled',
        'refunded' => 'Refunded',
    ];

    public function setFree()
    {
        return $this->changeState('free');
    }

    public function setUnpaid()
    {
        return $this->changeState('unpaid');
    }

    public function setProcessing()
    {
        return $this->changeState('processing');
    }

    public function setPartial()
    {
        return $this->changeState('partial');
    }

    public function setPaid()
    {
        return $this->changeState('paid');
    }

    public function setOutstanding()
    {
        return $this->changeState('outstanding');
    }

    public function setCanceled()
    {
        return $this->changeState('canceled');
    }

    public function setRefunded()
    {
        return $this->changeState('refunded');
    }
}
