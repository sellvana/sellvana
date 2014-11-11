<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Shipment_State_Carrier extends FCom_Core_Model_Abstract_State_Custom
{
    protected $_valueLabels = [
        'label' => 'Label Printed',
        'received' => 'Received',
        'shipped' => 'Shipped',
        'in_transit' => 'In Transit',
        'exception' => 'Exception',
        'delivered' => 'Delivered',
        'refused' => 'Refused',
        'returned' => 'Returned',
    ];

    public function setLabel()
    {
        return $this->changeState('label');
    }

    public function setReceived()
    {
        return $this->changeState('received');
    }

    public function setShipped()
    {
        return $this->changeState('shipped');
    }

    public function setInTransit()
    {
        return $this->changeState('in_transit');
    }

    public function setException()
    {
        return $this->changeState('exception');
    }

    public function setDelivered()
    {
        return $this->changeState('delivered');
    }

    public function setRefused()
    {
        return $this->changeState('refused');
    }

    public function setReturned()
    {
        return $this->changeState('returned');
    }
}
