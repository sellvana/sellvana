<?php

interface FCom_Sales_Method_Payment_Interface
{
    public function payOnCheckout();

    public function setSalesEntity($order, $options);
}