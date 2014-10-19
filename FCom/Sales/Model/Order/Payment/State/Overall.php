<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Payment_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'pending' => 'Pending',
        'canceled' => 'Canceled',
        'processing' => 'Processing',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        'void' => 'Void',
        'partial' => 'Partial',
    ];

    protected $_setValueNotificationTemplates =[
        'failed' => 'email/sales/order-payment-state-overall-failed',
        'refunded' => 'email/sales/order-payment-state-overall-refunded',
        'void' => 'email/sales/order-payment-state-overall-void',
    ];

    public function setPending()
    {
        return $this->changeState('pending');
    }

    public function setCanceled()
    {
        return $this->changeState('canceled');
    }

    public function setProcessing()
    {
        return $this->changeState('processing');
    }

    public function setPaid()
    {
        return $this->changeState('paid');
    }

    public function setFailed()
    {
        return $this->changeState('failed');
    }

    public function setRefunded()
    {
        return $this->changeState('refunded');
    }

    public function setVoid()
    {
        return $this->changeState('void');
    }

    public function setPartial()
    {
        return $this->changeState('partial');
    }
}
