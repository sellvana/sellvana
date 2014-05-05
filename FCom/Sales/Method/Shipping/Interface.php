<?php

interface FCom_Sales_Method_Shipping_Interface
{

    /**
     * Return available shipping services codes and description
     */
    public function getServices();
    public function getServicesSelected();
    public function getDefaultService();

    /**
     * Return shipping rate(cost) based on service, location and package parameters
     */
    public function getRateCallback($cart);

    public function getEstimate();

    /**
     * Return error message if getRateCallback was unsuccessefull
     */
    public function getError();

    /**
     * Return shipping service name like Fedex, UPS, etc...
     */
    public function getDescription();
}