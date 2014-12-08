<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Payment_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    const PENDING = 'pending',
        FAILED = 'failed',
        CANCELED = 'canceled',
        PROCESSING = 'processing',
        PAID = 'paid',
        REFUNDED = 'refunded',
        VOID = 'void',
        PARTIAL = 'partial';

    protected $_valueLabels = [
        self::PENDING => 'Pending',
        self::FAILED => 'Failed',
        self::CANCELED => 'Canceled',
        self::PROCESSING => 'Processing',
        self::PAID => 'Paid',
        self::REFUNDED => 'Refunded',
        self::VOID => 'Void',
        self::PARTIAL => 'Partial',
    ];

    protected $_setValueNotificationTemplates = [
        self::REFUNDED => 'email/sales/order-payment-state-overall-refunded',
        self::VOID => 'email/sales/order-payment-state-overall-void',
    ];

    public function setPending()
    {
        return $this->changeState(self::PENDING);
    }

    public function setFailed()
    {
        return $this->changeState(self::FAILED);
    }

    public function setCanceled()
    {
        return $this->changeState(self::CANCELED);
    }

    public function setProcessing()
    {
        return $this->changeState(self::PROCESSING);
    }

    public function setPaid()
    {
        return $this->changeState(self::PAID);
    }

    public function setRefunded()
    {
        return $this->changeState(self::REFUNDED);
    }

    public function setVoid()
    {
        return $this->changeState(self::VOID);
    }

    public function setPartial()
    {
        return $this->changeState(self::PARTIAL);
    }
}
