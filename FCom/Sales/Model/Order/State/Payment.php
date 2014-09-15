<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_State_Payment extends FCom_Sales_Model_Order_State_Abstract
{
    protected $_valueLabels = [
        'new' => 'New',
        'panding' => 'Pending',
        'authorized' => 'Authorized',
        'failed' => 'Failed',
        'declined' => 'Declined',
        'charged' => 'Charged',
        'refunded' => 'Refunded',
        'void' => 'Void',
        'canceled' => 'Canceled',
        'partial' => 'Partial',
    ];

    public function setNew()
    {
        return $this->changeState('new');
    }

    public function setPending()
    {
        return $this->changeState('pending');
    }

    public function setAuthorized()
    {
        return $this->changeState('authorized');
    }

    public function setFailed()
    {
        return $this->changeState('failed');
    }

    public function setDeclined()
    {
        return $this->changeState('declined');
    }

    public function setCharged()
    {
        return $this->changeState('charged');
    }

    public function setRefunded()
    {
        return $this->changeState('refunded');
    }

    public function setVoid()
    {
        return $this->changeState('void');
    }

    public function setCanceled()
    {
        return $this->changeState('canceled');
    }

    public function setPartial()
    {
        return $this->changeState('partial');
    }
}
