<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Payment_State_Processor extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'pending' => 'Pending',
        'authorizing' => 'Authorizing', // while in process
        'authorized' => 'Authorized',
        'refused' => 'Refused',
        'expired' => 'Expired',
        'captured' => 'Captured',
        'declined' => 'Declined',
        'failed' => 'Failed', // if there was an error
        'chargeback' => 'Charged Back',
        'refunded' => 'Refunded',
        'void' => 'Void',
        'canceled' => 'Canceled',
    ];

    public function setPending()
    {
        return $this->changeState('pending');
    }

    public function setAuthorizing()
    {
        return $this->changeState('authorizing');
    }

    public function setAuthorized()
    {
        return $this->changeState('authorized');
    }

    public function setRefused()
    {
        return $this->changeState('refused');
    }

    public function setCaptured()
    {
        return $this->changeState('captured');
    }

    public function setDeclined()
    {
        return $this->changeState('declined');
    }

    public function setFailed()
    {
        return $this->changeState('failed');
    }

    public function setChargedBack()
    {
        return $this->changeState('chargeback');
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
}
