<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_Shipment_State_Carrier extends Sellvana_Sales_Model_Order_State_Abstract
{
    const LABEL = 'label',
        RECEIVED = 'received',
        SHIPPED = 'shipped',
        IN_TRANSIT = 'in_transit',
        EXCEPTION = 'exception',
        DELIVERED = 'delivered',
        REFUSED = 'refused',
        RETURNED = 'returned';

    protected $_valueLabels = [
        self::LABEL => 'Label Printed',
        self::RECEIVED => 'Received',
        self::SHIPPED => 'Shipped',
        self::IN_TRANSIT => 'In Transit',
        self::EXCEPTION => 'Exception',
        self::DELIVERED => 'Delivered',
        self::REFUSED => 'Refused',
        self::RETURNED => 'Returned',
    ];

    public function setLabel()
    {
        return $this->changeState(self::LABEL);
    }

    public function setReceived()
    {
        return $this->changeState(self::RECEIVED);
    }

    public function setShipped()
    {
        return $this->changeState(self::SHIPPED);
    }

    public function setInTransit()
    {
        return $this->changeState(self::IN_TRANSIT);
    }

    public function setException()
    {
        return $this->changeState(self::EXCEPTION);
    }

    public function setDelivered()
    {
        return $this->changeState(self::DELIVERED);
    }

    public function setRefused()
    {
        return $this->changeState(self::REFUSED);
    }

    public function setReturned()
    {
        return $this->changeState(self::RETURNED);
    }
}
