<?php defined('BUCKYBALL_ROOT_DIR') || die();

interface Sellvana_Sales_Method_Shipping_Interface
{

    /**
     * Return available shipping services codes and description
     */
    public function getServices();
    public function getServicesSelected();

    public function fetchCartRates($cart = null);

    public function fetchPackageRates($package);

    /**
     * Return error message if getRateCallback was unsuccessefull
     */
    public function getError();

    /**
     * Return shipping service name like Fedex, UPS, etc...
     */
    public function getDescription();
}
