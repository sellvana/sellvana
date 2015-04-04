<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_State_Delivery extends FCom_Core_Model_Abstract_State_Concrete
{
    const VIRTUAL = 'virtual',
        PENDING = 'pending',
        PACKED = 'packed',
        SHIPPED = 'shipped',
        DELIVERED = 'delivered',
        RETURNED = 'returned',
        PARTIAL = 'partial';

    protected $_valueLabels = [
        self::VIRTUAL => 'Virtual',
        self::PENDING => 'Pending',
        self::PACKED => 'Packed',
        self::SHIPPED => 'Shipped',
        self::DELIVERED => 'Delivered',
        self::RETURNED => 'Returned',
        self::PARTIAL => 'Partial',
    ];

    protected $_setValueNotificationTemplates = [
        self::SHIPPED => 'email/sales/order-state-delivery-shipped',
        self::DELIVERED => 'email/sales/order-state-delivery-delivered',
        self::RETURNED => 'email/sales/order-state-delivery-returned',
    ];

    public function setVirtual()
    {
        return $this->changeState(self::VIRTUAL);
    }

    public function setPending()
    {
        return $this->changeState(self::PENDING);
    }

    public function setPacked()
    {
        return $this->changeState(self::PACKED);
    }

    public function setShipped()
    {
        return $this->changeState(self::SHIPPED);
    }

    public function setDelivered()
    {
        return $this->changeState(self::DELIVERED);
    }

    public function setReturned()
    {
        return $this->changeState(self::RETURNED);
    }

    public function setPartial()
    {
        return $this->changeState(self::PARTIAL);
    }
}
