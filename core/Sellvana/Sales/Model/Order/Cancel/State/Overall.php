<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_Cancel_State_Overall extends Sellvana_Sales_Model_Order_State_Abstract
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
        self::COMPLETE => 'Canceled',
    ];

    protected $_setValueNotificationTemplates =[
        self::REQUESTED => 'email/sales/order-cancel-state-overall-requested-admin',
        self::APPROVED => 'email/sales/order-cancel-state-overall-approved',
        self::DECLINED => 'email/sales/order-cancel-state-overall-declined',
    ];

    protected $_defaultValue = self::PENDING;

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

    public function setComplete()
    {
        return $this->changeState(self::COMPLETE);
    }
}
