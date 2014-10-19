<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Return_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'new' => 'New',
        'rma_sent' => 'RMA Sent',
        'expired' => 'Expired',
        'canceled' => 'Canceled',
        'received' => 'Received',
        'accepted' => 'Accepted',
        'restocked' => 'Re-stocked',
        'declined' => 'Declined',
    ];

    protected $_setValueNotificationTemplates =[
        'rma_sent' => 'email/sales/order-return-state-overall-rma_sent',
        'received' => 'email/sales/order-return-state-overall-received',
        'accepted' => 'email/sales/order-return-state-overall-accepted',
        'declined' => 'email/sales/order-return-state-overall-declined',
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

    public function setDeclined()
    {
        return $this->changeState('declined');
    }
}
