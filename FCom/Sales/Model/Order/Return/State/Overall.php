<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Payment_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'new' => 'New',
        'rma_sent' => 'RMA Sent',
        'expired' => 'Expired',
        'canceled' => 'Canceled',
        'accepted' => 'Accepted',
        'verified' => 'Verified',
        'restocked' => 'Re-stocked',
        'invalid' => 'Invalid',
        'damaged' => 'Damaged',
    ];

    protected $_setValueNotificationTemplates =[
        'refunded' => 'email/sales/order-state-payment-refunded',
        'void' => 'email/sales/order-state-overall-void',
    ];

    public function setNew()
    {
        return $this->changeState('new');
    }

    public function setRMASent()
    {
        return $this->changeState('rma_sent');
    }

    public function setExpired()
    {
        return $this->changeState('expired');
    }

    public function setCanceled()
    {
        return $this->changeState('canceled');
    }

    public function setReceived()
    {
        return $this->changeState('received');
    }

    public function setAccepted()
    {
        return $this->changeState('accepted');
    }

    public function setRestocked()
    {
        return $this->changeState('restocked');
    }

    public function setInvalid()
    {
        return $this->changeState('invalid');
    }

    public function setDamaged()
    {
        return $this->changeState('damaged');
    }
}
