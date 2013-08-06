<?php

interface FCom_Sales_Method_Payment_Interface
{
    public function payOnCheckout();

    /**
     * @param $order
     * @param $options
     * @return $this
     */
    public function setSalesEntity($order, $options);
}