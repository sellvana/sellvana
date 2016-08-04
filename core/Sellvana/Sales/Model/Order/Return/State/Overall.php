<?php

class Sellvana_Sales_Model_Order_Return_State_Overall extends Sellvana_Sales_Model_Order_State_Abstract
{
    const PENDING = 'pending',
        REQUESTED = 'requested',
        RMA_SENT = 'rma_sent',
        EXPIRED = 'expired',
        CANCELED = 'canceled',
        RECEIVED = 'received',
        APPROVED = 'approved',
        RESTOCKED = 'restocked',
        DECLINED = 'declined';

    protected $_valueLabels = [
        self::PENDING => 'Pending',
        self::REQUESTED => 'Requested',
        self::RMA_SENT => 'RMA Sent',
        self::EXPIRED => 'Expired',
        self::CANCELED => 'Canceled',
        self::RECEIVED => 'Received',
        self::APPROVED => 'Approved',
        self::RESTOCKED => 'Re-stocked',
        self::DECLINED => 'Declined',
    ];

    protected $_defaultMethods = [
        self::PENDING => 'setPending',
        self::REQUESTED => 'setRequested',
        self::RMA_SENT => 'setRMASent',
        self::EXPIRED => 'setExpired',
        self::CANCELED => 'setCanceled',
        self::RECEIVED => 'setReceived',
        self::APPROVED => 'setApproved',
        self::RESTOCKED => 'setRestocked',
        self::DECLINED => 'setDeclined',
    ];

    protected $_setValueNotificationTemplates =[
        self::RMA_SENT => 'email/sales/order-return-state-overall-rma_sent',
        self::RECEIVED => 'email/sales/order-return-state-overall-received',
        self::APPROVED => 'email/sales/order-return-state-overall-approved',
        self::DECLINED => 'email/sales/order-return-state-overall-declined',
    ];

    protected $_defaultValue = self::PENDING;

    protected $_defaultValueWorkflow = [
        self::PENDING => [self::REQUESTED],
        self::REQUESTED => [self::RMA_SENT],
        self::RMA_SENT => [self::RECEIVED, self::CANCELED, self::EXPIRED],
        self::EXPIRED => [self::PENDING],
        self::CANCELED => [self::PENDING],
        self::RECEIVED => [self::APPROVED, self::DECLINED],
        self::APPROVED => [self::RESTOCKED],
        self::DECLINED => [self::RESTOCKED],
    ];

    public function setPending()
    {
        return $this->changeState(self::PENDING);
    }

    public function setRequested()
    {
        return $this->changeState(self::REQUESTED);
    }

    public function setRMASent()
    {
        return $this->changeState(self::RMA_SENT);
    }

    public function setExpired()
    {
        return $this->changeState(self::EXPIRED);
    }

    public function setCanceled()
    {
        return $this->changeState(self::CANCELED);
    }

    public function setReceived()
    {
        return $this->changeState(self::RECEIVED);
    }

    public function setApproved()
    {
        return $this->changeState(self::APPROVED);
    }

    public function setRestocked()
    {
        return $this->changeState(self::RESTOCKED);
    }

    public function setDeclined()
    {
        return $this->changeState(self::DECLINED);
    }
}
