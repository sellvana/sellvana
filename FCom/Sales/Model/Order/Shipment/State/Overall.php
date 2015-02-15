<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Shipment_State_Overall extends FCom_Core_Model_Abstract_State_Custom
{
    const PENDING = 'pending',
        PACKING = 'packing',
        SHIPPING = 'shipping',
        SHIPPED = 'shipped',
        EXCEPTION = 'exception',
        DELIVERED = 'delivered',
        RETURNED = 'returned',
        CANCELED = 'canceled';

    protected $_valueLabels = [
        self::PENDING => 'Pending',
        self::PACKING => 'Packing',
        self::SHIPPING => 'Shipping',
        self::SHIPPED => 'Shipped',
        self::EXCEPTION => 'Exception',
        self::DELIVERED => 'Delivered',
        self::RETURNED => 'Returned',
        self::CANCELED => 'Canceled',
    ];

    protected $_setValueNotificationTemplates =[
        self::SHIPPED => 'email/sales/order-shipmend-state-overall-shipped',
        self::EXCEPTION => 'email/sales/order-shipmend-state-overall-exception',
        self::DELIVERED => 'email/sales/order-shipmend-state-overall-delivered',
    ];

    public function setPending()
    {
        return $this->changeState(self::PENDING);
    }

    public function setPacking()
    {
        return $this->changeState(self::PACKING);
    }

    public function setShipping()
    {
        return $this->changeState(self::SHIPPING);
    }

    public function setShipped()
    {
        return $this->changeState(self::SHIPPED);
    }

    public function setException()
    {
        return $this->changeState(self::EXCEPTION);
    }

    public function setDelivered()
    {
        return $this->changeState(self::DELIVERED);
    }

    public function setReturned()
    {
        return $this->changeState(self::RETURNED);
    }

    public function setCanceled()
    {
        return $this->changeState(self::CANCELED);
    }
}
