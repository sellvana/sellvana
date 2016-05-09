<?php

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
        self::COMPLETE => 'Complete',
    ];

    protected $_setValueNotificationTemplates =[
        self::REQUESTED => 'email/sales/order-cancel-state-overall-requested-admin',
        self::APPROVED => 'email/sales/order-cancel-state-overall-approved',
        self::DECLINED => 'email/sales/order-cancel-state-overall-declined',
    ];

    protected $_defaultValue = self::PENDING;
    
    protected $_defaultMethods = [
        self::REQUESTED => 'setRequested',
        self::PENDING => 'setPending',
        self::APPROVED => 'setApproved',
        self::DECLINED => 'setDeclined',
        self::COMPLETE => 'setComplete',
    ];

    protected $_defaultValueWorkflow = [
        self::PENDING => [self::APPROVED, self::DECLINED],
        self::APPROVED => [self::COMPLETE],
        self::DECLINED => [self::COMPLETE],
        self::COMPLETE => [],
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

    public function setComplete()
    {
        return $this->changeState(self::COMPLETE);
    }
}
