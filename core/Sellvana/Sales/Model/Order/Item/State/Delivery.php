<?php

class Sellvana_Sales_Model_Order_Item_State_Delivery extends Sellvana_Sales_Model_Order_State_Abstract
{
    const VIRTUAL = 'virtual',
        PENDING = 'pending',
        PACKED = 'packed',
        SHIPPED = 'shipped',
        DELIVERED = 'delivered',
        PARTIAL = 'partial';

    protected $_valueLabels = [
        self::VIRTUAL => 'Virtual',
        self::PENDING => 'Pending',
        self::PACKED => 'Packed',
        self::SHIPPED => 'Shipped',
        self::DELIVERED => 'Delivered',
        self::PARTIAL => 'Partial',
    ];
    
    protected $_defaultMethods = [
        self::VIRTUAL => 'setVirtual',
        self::PENDING => 'setPending',
        self::PACKED => 'setPacked',
        self::SHIPPED => 'setShipped',
        self::DELIVERED => 'setDelivered',
        self::PARTIAL => 'setPartial',
    ];

    protected $_setValueNotificationTemplates =[
        self::SHIPPED => 'email/sales/order-item-state-delivery-shipped',
        self::DELIVERED => 'email/sales/order-item-state-delivery-delivered',
    ];

    protected $_defaultValue = self::PENDING;

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

    public function isComplete()
    {
        return in_array($this->getValue(), [self::VIRTUAL, self::SHIPPED, self::DELIVERED]);
    }

    public function calcState()
    {
        /** @var Sellvana_Sales_Model_Order_Item $model */
        $model = $this->getContext()->getModel();

        if ($this->getValue() === self::DELIVERED) {
            return $this;
        }

        if ($model->get('qty_shipped') == $model->get('qty_ordered')) {
            return $this->setShipped();
        }
        if ($model->get('qty_shipped') > 0) {
            return $this->setPartial();
        }

        return $this;
    }
}
