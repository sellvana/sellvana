<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Refund_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
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

    protected $_setValueNotificationTemplates = [
        self::SUPERVISOR_PENDING => 'email/sales/order-refund-state-payment-super_pending-admin',
        self::SUPERVISOR_AUTHORIZED => 'email/sales/order-refund-state-payment-super_auth',
        self::REFUNDED => 'email/sales/order-refund-state-payment-refunded',
        self::FAILED => 'email/sales/order-refund-state-overall-failed',
        self::CANCELED => 'email/sales/order-refund-state-overall-canceled',
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
        return $this->changeState(self::VOID);
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
