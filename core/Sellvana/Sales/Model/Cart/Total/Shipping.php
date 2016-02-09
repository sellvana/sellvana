<?php

/**
 * Class Sellvana_Sales_Model_Cart_Total_Shipping
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_Sales_Model_Cart_Total_Shipping extends Sellvana_Sales_Model_Cart_Total_Abstract
{
    protected $_code = 'shipping';
    protected $_label = 'Shipping & Handling';
    protected $_cartField = 'shipping_price';
    protected $_sortOrder = 40;

    public function calculate()
    {
        $cart = $this->_cart;
        if ($cart->get('recalc_shipping_rates')) {
            $methods = $this->Sellvana_Sales_Main->getShippingMethods();
            $weight = 0;
            $rates = [];
            if ($methods) {
                foreach ($methods as $methodCode => $method) {
                    $rates[$methodCode] = $method->fetchCartRates($cart);
                    if (!empty($rates[$methodCode]['weight'])) {
                        $weight = $rates[$methodCode]['weight'];
                    }
                }
            }
            $cart->set([
                'shipping_weight' => $weight,
                'recalc_shipping_rates' => 0,
            ])->setData('shipping_rates', $rates);
        } else {
            $rates = $cart->getData('shipping_rates');
        }

        if ($rates) {
            list($selMethod, $selService) = $this->_findSelectedMethodService($rates);
        } else {
            $selMethod = null;
            $selService = null;
        }

        $this->_value = $selMethod && $selService ? $rates[$selMethod][$selService]['price'] : 0;
        $this->_storeCurrencyValue = $this->_cart->convertToStoreCurrency($this->_value);

        $cart->set([
            'shipping_method' => $selMethod,
            'shipping_service' => $selService,
            'shipping_price' => $this->_value,
        ])->setData('store_currency/shipping_price', $this->_storeCurrencyValue);

        /** @var Sellvana_Sales_Model_Cart_Total_GrandTotal $grandTotalModel */
        $grandTotalModel = $this->_cart->getTotalByType('grand_total');
        $grandTotalModel->addComponent('shipping', $this->_value, $this->_storeCurrencyValue);

        return $this;
    }

    protected function _findSelectedMethodService($rates)
    {
        $minRates = [];
        foreach ($rates as $methodCode => $methodRates) {
            if (!empty($rates[$methodCode]['error'])) {
                continue;
            }
            $minService = null;
            $minPrice = 99999;
            foreach ($methodRates as $serviceCode => $serviceRate) {
                if ($serviceRate['price'] < $minPrice) {
                    $minService = $serviceCode;
                    $minPrice = $serviceRate['price'];
                }
            }
            if ($minService) {
                $minRates[$methodCode] = ['service' => $minService, 'price' => $minPrice];
            }
        }

        $defMethod = $this->BConfig->get('modules/Sellvana_Sales/default_shipping_method');
        $selMethod = $this->_cart->get('shipping_method');
        $selService = $this->_cart->get('shipping_service');

        if (!$selMethod && !$selService) { // if not set at all

            if (empty($minRates[$defMethod])) { // if no rate for default method
                $minPrice = 99999;
                foreach ($minRates as $methodCode => $minService) { // find cheapest method
                    if ($minService['price'] < $minPrice) {
                        $minPrice = $minService['price'];
                        $selMethod = $methodCode;
                    }
                }
            } else { // or set it as selected
                $selMethod = $defMethod;
            }
            $selService = null; // request to find cheapest service for selected method

        }
// WTFPHP???
//        elseif (empty($rates[$selMethod][$selService])) { // if selected service is not available
//            var_dump($selMethod, $selService, array_key_exists($selService, $rates[$selMethod]), $rates[$selMethod][(string)$selService]['price']); exit;
//            $selService = null; // request to find cheapest service for selected method
//
//        }
        $found = false;
        foreach ($rates[$selMethod] as $svc => $svcData) {
            if ($svc === $selService) {
                $found = true;
            }
        }
        if (!$found) {
            $selService = null;
        }


        if (!$selService && $selMethod && !empty($minRates[$selMethod])) { // if service is not set or was reset, set to cheapest
            $selService = $minRates[$selMethod]['service'];
        }

        return [$selMethod, $selService];
    }

    public function isHidden()
    {
        if ($this->_cart->get('shipping_free')) {
            return false;
        }
        return parent::isHidden();
    }
}