<?php

/**
 * Class Sellvana_ShippingPlain_ShippingMethod
 */
class Sellvana_ShippingPlain_ShippingMethod extends Sellvana_Sales_Method_Shipping_Abstract
{
    /**
     * @var string
     */
    protected $_name = 'Plain Shipping';
    protected $_code = 'plain';
    protected $_configPath = 'modules/Sellvana_ShippingPlain';
    /**
     *
     */
    const FREE_SHIPPING = "free";

    /**
     * @return string
     */
    public function _fetchRates($data)
    {
        return [
            'success' => 1,
            'rates' => [
                '_01' => ['price' => 10, 'max_days' => 2],
                '_12' => ['price' => 0, 'max_days' => 5],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getServices()
    {
        return [
            '_01' => 'Air',
            '_12' => 'Ground',
        ];
    }
}
