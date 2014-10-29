<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Refund_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'pending' => 'Pending',
        'super_pending' => 'Pending Supervisor',
        'super_auth' => 'Supervisor Authorized',
        'partial' => 'Partial',
        'refunded' => 'Refunded',
        'failed' => 'Failed',
        'canceled' => 'Canceled',
    ];

    protected $_setValueNotificationTemplates = [
        'super_pending' => 'email/sales/order-refund-state-payment-super_pending-admin',
        'super_auth' => 'email/sales/order-refund-state-payment-super_auth',
        'refunded' => 'email/sales/order-refund-state-payment-refunded',
        'failed' => 'email/sales/order-refund-state-overall-failed',
        'canceled' => 'email/sales/order-refund-state-overall-canceled',
    ];

    public function setPending()
    {
        return $this->changeState('pending');
    }

    public function setSuperPending()
    {
        return $this->changeState('super_pending');
    }

    public function setSuperAuth()
    {
        return $this->changeState('super_auth');
    }

    public function setPartial()
    {
        return $this->changeState('void');
    }

    public function setRefunded()
    {
        return $this->changeState('refunded');
    }

    public function setFailed()
    {
        return $this->changeState('failed');
    }

    public function setCanceled()
    {
        return $this->changeState('canceled');
    }
}
