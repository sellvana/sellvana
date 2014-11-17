<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Cancel_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'requested' => 'Requested',
        'new' => 'New',
        'approved' => 'Approved',
        'declined' => 'Declined',
        'canceled' => 'Canceled',
    ];

    protected $_setValueNotificationTemplates =[
        'requested' => 'email/sales/order-cancel-state-overall-requested-admin',
        'approved' => 'email/sales/order-cancel-state-overall-approved',
        'declined' => 'email/sales/order-cancel-state-overall-declined',
    ];

    public function setRequested()
    {
        return $this->changeState('requested');
    }

    public function setNew()
    {
        return $this->changeState('new');
    }

    public function setApproved()
    {
        return $this->changeState('approved');
    }

    public function setDeclined()
    {
        return $this->changeState('declined');
    }

    public function setCanceled()
    {
        return $this->changeState('canceled');
    }

}
