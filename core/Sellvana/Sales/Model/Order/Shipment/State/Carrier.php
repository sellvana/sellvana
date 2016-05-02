<?php

class Sellvana_Sales_Model_Order_Shipment_State_Carrier extends Sellvana_Sales_Model_Order_State_Abstract
{
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

    public function calcState()
    {
        /** @var Sellvana_Sales_Model_Order_Shipment $shipment */
        $shipment = $this->getContext()->getModel();
        $currentState = ($this->getValue() !== '') ? $this->getValue() : $this->_defaultValue;

        $state = self::NA;
        $orderedStates = array_keys($this->_valueLabels);
        $statePriorities = array_flip($orderedStates);
        $statePriorities[self::NA] = 999;
        $statePriorities[self::EXCEPTION] = -1;
        $orderedStates = array_flip($statePriorities);

        foreach ($shipment->packages() as $package) {
            $packageState = $package->state()->overall()->getValue();
            if (!$packageState) {
                continue;
            }
            $state = $orderedStates[min($statePriorities[$state], $statePriorities[$packageState])];
        }

        if ($statePriorities[$state] > $statePriorities[$currentState]) {
            $this->changeState($state);
        }
    }
}
