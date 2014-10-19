<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_State_Comment extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'none' => 'None',
        'received' => 'Received (Waiting for admin)',
        'sent' => 'Sent (Waiting for customer)',
        'closed' => 'Closed',
        'auto_closed' => 'Auto-Closed',
    ];

    protected $_setValueNotificationTemplates = [
        'received' => [
            'email/sales/order-state-comment-received',
            'email/sales/order-state-comment-received-admin',
        ],
        'sent' => 'email/sales/order-state-comment-sent',
        'auto_closed' => 'email/sales/order-state-comment-auto_closed',
    ];

    public function setNone()
    {
        return $this->changeState('none');
    }

    public function setReceived()
    {
        return $this->changeState('received');
    }

    public function setSent()
    {
        return $this->changeState('sent');
    }

    public function setClosed()
    {
        return $this->changeState('closed');
    }

    public function setAutoClosed()
    {
        return $this->changeState('auto_closed');
    }
}
