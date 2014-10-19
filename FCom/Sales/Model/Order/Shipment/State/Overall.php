<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Shipment_State_Overall extends FCom_Core_Model_Abstract_State_Custom
{
    protected $_valueLabels = [
        'new' => 'New',
        'packing' => 'Packing',
        'shipping' => 'Shipping',
        'shipped' => 'Shipped',
        'exception' => 'Exception',
        'delivered' => 'Delivered',
        'returned' => 'Returned',
        'canceled' => 'Canceled',
    ];

    protected $_setValueNotificationTemplates =[
        'shipped' => 'email/sales/order-shipmend-state-overall-shipped',
        'exception' => 'email/sales/order-shipmend-state-overall-exception',
        'delivered' => 'email/sales/order-shipmend-state-overall-delivered',
    ];

    public function setNew()
    {
        return $this->changeState('new');
    }

    public function setPacking()
    {
        return $this->changeState('packing');
    }

    public function setShipping()
    {
        return $this->changeState('shipping');
    }

    public function setShipped()
    {
        return $this->changeState('shipped');
    }

    public function setException()
    {
        return $this->changeState('exception');
    }

    public function setDelivered()
    {
        return $this->changeState('delivered');
    }

    public function setReturned()
    {
        return $this->changeState('returned');
    }

    public function setCanceled()
    {
        return $this->changeState('canceled');
    }
}
