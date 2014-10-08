<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Item_State_Delivery extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'virtual' => 'Virtual',
        'pending' => 'Pending',
        'packed' => 'Packed',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'returned' => 'Returned',
        'partial' => 'Partial',
    ];

    protected $_setValueNotificationTemplates =[
        'shipped' => 'email/sales/order-item-state-delivery-shipped',
        'delivered' => 'email/sales/order-item-state-delivery-delivered',
        'returned' => 'email/sales/order-item-state-delivery-returned',
    ];

    public function setPending()
    {
        return $this->changeState('pending');
    }

    public function setPacked()
    {
        return $this->changeState('packed');
    }

    public function setShipped()
    {
        return $this->changeState('shipped');
    }

    public function setDelivered()
    {
        return $this->changeState('delivered');
    }

    public function setReturned()
    {
        return $this->changeState('returned');
    }

    public function setPartial()
    {
        return $this->changeState('partial');
    }
}
