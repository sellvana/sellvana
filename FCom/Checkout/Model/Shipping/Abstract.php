<?php

abstract class FCom_Checkout_Model_Shipping_Abstract extends BClass
{
    /**
     * Return available shipping services codes and description
     */
    abstract public function getServices();
    abstract public function getServicesSelected();
    abstract public function getDefaultService();

    /**
     * Return shipping rate(cost) based on service, location and package parameters
     */
    abstract public function getRateCallback($cart);

    abstract public function getEstimate();

    /**
     * Return error message if getRateCallback was unsuccessefull
     */
    abstract  public function getError();

    /**
     * Return shipping service name like Fedex, UPS, etc...
     */
    abstract public function getDescription();
}