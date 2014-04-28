<?php

interface FCom_Sales_Method_Payment_Interface
{
    public function payOnCheckout();

    /**
     * @param $order
     * @param $options
     * @return $this
     */
    public function setSalesEntity( $order, $options );

    /**
     * Set any details gathered during checkout process
     * @param array $details
     * @return $this
     */
    public function setDetails( $details );

    /**
     * Get public data
     *
     * Get data which can be saved, should not include any sensitive data such as credit card numbers, personal ids, etc.
     * @return array
     */
    public function getPublicData();
}