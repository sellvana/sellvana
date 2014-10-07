<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Payment_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        'void' => 'Void',
        'partial' => 'Partial',
    ];

    protected $_setValueNotificationTemplates =[
        'refunded' => 'email/sales/order-state-payment-refunded',
        'void' => 'email/sales/order-state-overall-void',
    ];

    public function setPending()
    {
        return $this->changeState('pending');
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
