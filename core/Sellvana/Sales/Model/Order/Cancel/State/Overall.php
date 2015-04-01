<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_Cancel_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    const REQUESTED = 'requested',
        PENDING = 'pending',
        APPROVED = 'approved',
        DECLINED = 'declined',
        COMPLETE = 'complete';

    protected $_valueLabels = [
        self::REQUESTED => 'Requested',
        self::PENDING => 'Pending',
        self::APPROVED => 'Approved',
        self::DECLINED => 'Declined',
        self::CANCELED => 'Canceled',
    ];

    protected $_setValueNotificationTemplates =[
        self::REQUESTED => 'email/sales/order-cancel-state-overall-requested-admin',
        self::APPROVED => 'email/sales/order-cancel-state-overall-approved',
        self::DECLINED => 'email/sales/order-cancel-state-overall-declined',
    ];

    public function setRequested()
    {
        return $this->changeState(self::REQUESTED);
    }

    public function setPending()
    {
        return $this->changeState(self::PENDING);
    }

    public function setApproved()
    {
        return $this->changeState(self::APPROVED);
    }

    public function setDeclined()
    {
        return $this->changeState(self::DECLINED);
    }

    public function setCanceled()
    {
        return $this->changeState(self::CANCELED);
    }

}
