<?php

class Sellvana_Sales_Model_Order_Refund_State_Overall extends Sellvana_Sales_Model_Order_State_Abstract
{
    const PENDING = 'pending',
        SUPERVISOR_PENDING = 'super_pending',
        SUPERVISOR_AUTHORIZED = 'super_auth',
        PARTIAL = 'partial',
        REFUNDED = 'refunded',
        FAILED = 'failed',
        CANCELED = 'canceled';

    protected $_valueLabels = [
        self::PENDING => 'Pending',
        self::SUPERVISOR_PENDING => 'Pending Supervisor',
        self::SUPERVISOR_AUTHORIZED => 'Supervisor Authorized',
        self::PARTIAL => 'Partial',
        self::REFUNDED => 'Refunded',
        self::FAILED => 'Failed',
        self::CANCELED => 'Canceled',
    ];

    protected $_defaultMethods = [
        self::PENDING => 'setPending',
        self::SUPERVISOR_PENDING => 'setSuperPending',
        self::SUPERVISOR_AUTHORIZED => 'setSuperAuth',
        self::PARTIAL => 'setPartial',
        self::REFUNDED => 'setRefunded',
        self::FAILED => 'setFailed',
        self::CANCELED => 'setCanceled',
    ];

    protected $_setValueNotificationTemplates = [
        self::SUPERVISOR_PENDING => 'email/sales/order-refund-state-payment-super_pending-admin',
        self::SUPERVISOR_AUTHORIZED => 'email/sales/order-refund-state-payment-super_auth',
        self::REFUNDED => 'email/sales/order-refund-state-payment-refunded',
        self::FAILED => 'email/sales/order-refund-state-overall-failed',
        self::CANCELED => 'email/sales/order-refund-state-overall-canceled',
    ];

    protected $_defaultValue = self::PENDING;

    protected $_defaultValueWorkflow = [
        self::PENDING => [self::SUPERVISOR_PENDING],
        self::SUPERVISOR_PENDING => [self::SUPERVISOR_AUTHORIZED],
        self::SUPERVISOR_AUTHORIZED => [self::REFUNDED, self::PARTIAL],
        self::PARTIAL => [self::REFUNDED],
        self::REFUNDED => [],
        self::FAILED => [self::PENDING],
        self::CANCELED => [],
    ];

    public function setPending()
    {
        return $this->changeState(self::PENDING);
    }

    public function setSuperPending()
    {
        return $this->changeState(self::SUPERVISOR_PENDING);
    }

    public function setSuperAuth()
    {
        return $this->changeState(self::SUPERVISOR_AUTHORIZED);
    }

    public function setPartial()
    {
        return $this->changeState(self::PARTIAL);
    }

    public function setRefunded()
    {
        return $this->changeState(self::REFUNDED);
    }

    public function setFailed()
    {
        return $this->changeState(self::FAILED);
    }

    public function setCanceled()
    {
        return $this->changeState(self::CANCELED);
    }
}
