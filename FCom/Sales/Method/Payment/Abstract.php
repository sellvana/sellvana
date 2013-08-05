<?php

abstract class FCom_Sales_Method_Payment_Abstract extends BClass implements
    FCom_Sales_Method_Payment_Interface
{
    protected $cart;
    protected $salesOptions;
    protected $salesEntity;
    protected $_sortOrder = 50;
    protected $_name;

    public function getName()
    {
        return $this->_name;
    }

    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    /**
     * Assign sales entity
     *
     * Prepare to pay for sales object here
     *
     * @param $order
     * @param $options
     * @return $this
     */
    public function setSalesEntity($order, $options)
    {
        $this->salesEntity = $order;
        $this->salesOptions = $options;

        return $this;
    }

    /**
     * @param $cart
     * @return $this
     * @internal This replaces initCart in basic payment
     */
    public function setCartEntity($cart)
    {
        $this->cart = $cart;
        return $this;
    }

    public function asArray()
    {
        return array("name" => $this->getName());
    }
}
