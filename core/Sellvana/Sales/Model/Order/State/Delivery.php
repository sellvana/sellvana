<?php

class Sellvana_Sales_Model_Order_State_Delivery extends Sellvana_Sales_Model_Order_State_Abstract
{
    const VIRTUAL = 'virtual',
        PENDING = 'pending',
        PACKED = 'packed',
        SHIPPED = 'shipped',
        DELIVERED = 'delivered',
        PARTIAL = 'partial';

    protected $_valueLabels = [
        self::VIRTUAL => (('Virtual')),
        self::PENDING => (('Pending')),
        self::PACKED => (('Packed')),
        self::SHIPPED => (('Shipped')),
        self::DELIVERED => (('Delivered')),
        self::PARTIAL => (('Partial')),
    ];

    protected $_setValueNotificationTemplates = [
        self::SHIPPED => 'email/sales/order-state-delivery-shipped',
        self::DELIVERED => 'email/sales/order-state-delivery-delivered',
    ];

    protected $_defaultValue = self::PENDING;

    protected $_defaultValueWorkflow = [
        self::VIRTUAL => [],
        self::PENDING => [self::PACKED, self::SHIPPED, self::PARTIAL],
        self::PACKED => [self::SHIPPED, self::PARTIAL],
        self::SHIPPED => [self::DELIVERED],
        self::DELIVERED => [],
        self::PARTIAL => [self::SHIPPED, self::DELIVERED],
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

    public function setPartial()
    {
        return $this->changeState(self::PARTIAL);
    }

    public function isVirtual()
    {
        return $this->getValue() == self::VIRTUAL;
    }

    public function isComplete()
    {
        return in_array($this->getValue(), [self::VIRTUAL, self::SHIPPED, self::DELIVERED]);
    }

    public function calcState()
    {
        $itemStates = $this->getItemStateStatistics('delivery');

        $virtual   = Sellvana_Sales_Model_Order_Item_State_Delivery::VIRTUAL;
        $pending   = Sellvana_Sales_Model_Order_Item_State_Delivery::PENDING;
        $packed    = Sellvana_Sales_Model_Order_Item_State_Delivery::PACKED;
        $shipped   = Sellvana_Sales_Model_Order_Item_State_Delivery::SHIPPED;
        $delivered = Sellvana_Sales_Model_Order_Item_State_Delivery::DELIVERED;
        $partial   = Sellvana_Sales_Model_Order_Item_State_Delivery::PARTIAL;

        if (sizeof($itemStates) === 1) {
            if (!empty($itemStates[$virtual])) {
                return $this->setVirtual();
            } elseif (!empty($itemStates[$pending])) {
                return $this->setPending();
            } elseif (!empty($itemStates[$packed])) {
                return $this->setPacked();
            }
        }
        if (!empty($itemStates[$pending]) || !empty($itemStates[$packed]) || !empty($itemStates[$partial])) {
            return $this->setPartial();
        }
        if (!empty($itemStates[$shipped]) && empty($itemStates[$pending])
            && empty($itemStates[$packed]) && empty($itemStates[$partial])
        ) {
            return $this->setShipped();
        }
        if (!empty($itemStates[$delivered]) && empty($itemStates[$pending])
            && empty($itemStates[$packed]) && empty($itemStates[$partial])
        ) {
            return $this->setDelivered();
        }

        return $this;
    }
}
