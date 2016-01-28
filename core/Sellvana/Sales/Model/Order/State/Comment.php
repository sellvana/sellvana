<?php

class Sellvana_Sales_Model_Order_State_Comment extends Sellvana_Sales_Model_Order_State_Abstract
{
    const NONE = 'none',
        RECEIVED = 'received',
        PROCESSING = 'processing',
        DELEGATED = 'delegated',
        SENT = 'sent',
        CLOSED = 'closed',
        AUTO_CLOSED = 'auto_closed';

    protected $_valueLabels = [
        self::NONE => 'None',
        self::RECEIVED => 'Received (Waiting for admin)',
        self::PROCESSING => 'Processing',
        self::DELEGATED => 'Delegated',
        self::SENT => 'Sent (Waiting for customer)',
        self::CLOSED => 'Closed',
        self::AUTO_CLOSED => 'Auto-Closed',
    ];

    protected $_setValueNotificationTemplates = [
        self::RECEIVED => [
            'email/sales/order-state-comment-received',
            'email/sales/order-state-comment-received-admin',
        ],
        self::DELEGATED => 'email/sales/order-state-comment-delegated-admin',
        self::SENT => 'email/sales/order-state-comment-sent',
        self::AUTO_CLOSED => 'email/sales/order-state-comment-auto_closed',
    ];

    protected $_defaultValue = self::NONE;

    public function setNone()
    {
        return $this->changeState(self::NONE);
    }

    public function setReceived()
    {
        return $this->changeState(self::RECEIVED);
    }

    public function setDelegated()
    {
        return $this->changeState(self::DELEGATED);
    }

    public function setSent()
    {
        return $this->changeState(self::SENT);
    }

    public function setClosed()
    {
        return $this->changeState(self::CLOSED);
    }

    public function setAutoClosed()
    {
        return $this->changeState(self::AUTO_CLOSED);
    }

    public function calcState()
    {
        return $this;
    }
}
