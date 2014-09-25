<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_State_Delivery extends FCom_Sales_Model_Order_State_Abstract
{
    protected $_valueLabels = [
        'pending' => 'Pending',
        'packed' => 'Packed',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'returned' => 'Returned',
        'partial' => 'Partial',
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
