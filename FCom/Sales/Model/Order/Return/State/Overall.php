<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Return_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'requested' => 'Requested',
        'new' => 'New',
        'rma_sent' => 'RMA Sent',
        'expired' => 'Expired',
        'canceled' => 'Canceled',
        'received' => 'Received',
        'approved' => 'Approved',
        'restocked' => 'Re-stocked',
        'declined' => 'Declined',
    ];

    protected $_setValueNotificationTemplates =[
        'rma_sent' => 'email/sales/order-return-state-overall-rma_sent',
        'received' => 'email/sales/order-return-state-overall-received',
        'approved' => 'email/sales/order-return-state-overall-approved',
        'declined' => 'email/sales/order-return-state-overall-declined',
    ];

    public function setRequested()
    {
        return $this->changeState('new');
    }

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

    public function setApproved()
    {
        return $this->changeState('approved');
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
