<?php

/**
 * Class Sellvana_Sales_Workflow_Abstract
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 */
abstract class Sellvana_Sales_Workflow_Abstract extends BClass
{
    /**
     * @param $args
     * @return Sellvana_Customer_Model_Customer
     */
    protected function _getCustomer($args)
    {
        if (!empty($args['customer'])) {
            $customer = $args['customer'];
        } else {
            $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        }
        return $customer;
    }

    /**
     * @param $args
     * @param bool $createIfNeeded
     * @return Sellvana_Sales_Model_Cart
     */
    protected function _getCart($args, $createIfNeeded = false)
    {
        if (!empty($args['cart'])) {
            $cart = $args['cart'];
        } else {
            $cart = $this->Sellvana_Sales_Model_Cart->sessionCart($createIfNeeded);
        }
        return $cart;
    }
}
