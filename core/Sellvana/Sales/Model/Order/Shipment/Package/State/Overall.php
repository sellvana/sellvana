<?php

class Sellvana_Sales_Model_Order_Shipment_Package_State_Overall extends Sellvana_Sales_Model_Order_State_Abstract
{
    protected static $_origClass = __CLASS__;

    const NA = 'na',
        PENDING = 'pending',
        LABEL = 'label',
        RECEIVED = 'received',
        SHIPPED = 'shipped',
        IN_TRANSIT = 'in_transit',
        EXCEPTION = 'exception',
        DELIVERED = 'delivered',
        REFUSED = 'refused',
        RETURNED = 'returned';

    protected $_valueLabels = [
        self::NA => 'N/A',
        self::PENDING => 'Pending',
        self::LABEL => 'Label Printed',
        self::RECEIVED => 'Received',
        self::SHIPPED => 'Shipped',
        self::IN_TRANSIT => 'In Transit',
        self::EXCEPTION => 'Exception',
        self::DELIVERED => 'Delivered',
        self::REFUSED => 'Refused',
        self::RETURNED => 'Returned',
    ];

    protected $_defaultMethods = [
        self::NA => 'setNotApplicable',
        self::PENDING => 'setPending',
        self::LABEL => 'setLabel',
        self::RECEIVED => 'setReceived',
        self::SHIPPED => 'setShipped',
        self::IN_TRANSIT => 'setInTransit',
        self::EXCEPTION => 'setException',
        self::DELIVERED => 'setDelivered',
        self::REFUSED => 'setRefused',
        self::RETURNED => 'setReturned',
    ];

    protected $_defaultValue = self::PENDING;

    public function setNotApplicable()
    {
        return $this->changeState(self::NA);
    }

    public function setPending()
    {
        return $this->changeState(self::PENDING);
    }

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

    public function sendNotification($onUnset = false, $value = null)
    {
        return false;
    }


    /*
    public function calcState()
    {
        /** @var Sellvana_Sales_Model_Order_Shipment $shipment * /
        $shipment = $this->getContext()->getModel();
        $order = $shipment->order();

        $sItems = $shipment->items();
        $oItems = $order->items();

        foreach ($sItems as $sItem) {
            $oItem = $oItems[$sItem->get('order_item_id')];
        }

        return $this;
    }
    */
}
