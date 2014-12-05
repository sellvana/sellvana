<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Payment_State_Processor extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'pending' => 'Pending',
        'ext_redirected' => 'External Redirected',
        'ext_returned' => 'External Returned',
        'authorizing' => 'Authorizing', // while in process
        'authorized' => 'Authorized',
        'refused' => 'Refused',
        'expired' => 'Expired',
        'captured' => 'Captured',
        'declined' => 'Declined',
        'error' => 'Error',
        'chargeback' => 'Charged Back',
        'refunded' => 'Refunded',
        'void' => 'Void',
        'canceled' => 'Canceled',
    ];

    public function setPending()
    {
        return $this->changeState('pending');
    }

    public function setExtRedirected()
    {
        return $this->changeState('ext_redirected');
    }

    public function setExtReturned()
    {
        return $this->changeState('ext_returned');
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

    public function setError()
    {
        return $this->changeState('error');
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
