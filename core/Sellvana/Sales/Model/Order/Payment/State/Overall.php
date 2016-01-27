<?php

class Sellvana_Sales_Model_Order_Payment_State_Overall extends Sellvana_Sales_Model_Order_State_Abstract
{
    const PENDING = 'pending',
        OFFLINE = 'offline',
        EXT_SENT = 'ext_sent',
        EXT_RETURNED = 'ext_returned',
        FAILED = 'failed',
        CANCELED = 'canceled',
        PROCESSING = 'processing',
        PARTIAL_PAID = 'partial_paid',
        PAID = 'paid',
        PARTIAL_REFUNDED = 'partial_refunded',
        REFUNDED = 'refunded',
        CHARGEDBACK = 'chargedback';

    protected $_valueLabels = [
        self::PENDING => 'Pending',
        self::OFFLINE => 'Offline Payment',
        self::EXT_SENT => 'Sent to External Checkout',
        self::EXT_RETURNED => 'Returned from External Checkout',
        self::FAILED => 'Failed',
        self::CANCELED => 'Canceled',
        self::PROCESSING => 'Processing',
        self::PARTIAL_PAID => 'Partial Paid',
        self::PAID => 'Paid',
        self::PARTIAL_REFUNDED => 'Partial Refunded',
        self::REFUNDED => 'Refunded',
        self::CHARGEDBACK => 'Charged Back',
    ];

    protected $_setValueNotificationTemplates = [
        self::REFUNDED => 'email/sales/order-payment-state-overall-refunded',
    ];

    protected $_defaultValue = self::PENDING;

    public function setPending()
    {
        return $this->changeState(self::PENDING);
    }

    public function setOffline()
    {
        return $this->changeState(self::OFFLINE);
    }

    public function setExtSent()
    {
        return $this->changeState(self::EXT_SENT);
    }

    public function setExtReturned()
    {
        return $this->changeState(self::EXT_RETURNED);
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

    public function setPartialPaid()
    {
        return $this->changeState(self::PARTIAL_PAID);
    }

    public function setPaid()
    {
        return $this->changeState(self::PAID);
    }

    public function setPartialRefunded()
    {
        return $this->changeState(self::PARTIAL_REFUNDED);
    }

    public function setRefunded()
    {
        return $this->changeState(self::REFUNDED);
    }

    public function setChargedBack()
    {
        return $this->changeState(self::CHARGEDBACK);
    }
}
