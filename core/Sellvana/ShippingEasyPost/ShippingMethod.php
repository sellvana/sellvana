<?php

/**
 * Class Sellvana_ShippingUps_ShippingMethod
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property Sellvana_MultiCurrency_Main $Sellvana_MultiCurrency_Main
 */
class Sellvana_ShippingEasyPost_ShippingMethod extends Sellvana_Sales_Method_Shipping_Abstract
{
    const MODE_TEST = 'test';
    const MODE_PROD = 'prod';

    protected $_name = 'EasyPost';
    protected $_code = 'easypost';
    protected $_configPath = 'modules/Sellvana_ShippingEasyPost';

    protected function _fetchRates($data)
    {
        $baseCur = $this->BConfig->get('modules/FCom_Core/base_currency', 'USD');
        if ($baseCur !== 'USD' && !$this->BModuleRegistry->isLoaded('Sellvana_MultiCurrency')) {
            $result = [
                'error' => 1,
                'message' => 'EasyPost works only with USD prices. Either change the base currency, or enable Sellvana_MultiCurrency extension',
            ];
            return $result;

        }
        $config = $this->BConfig->get($this->_configPath);
        $data = array_merge($config, $data);

        $data = $this->_applyDefaultPackageConfig($data);

        if ($data['mode'] === self::MODE_TEST && !empty($data['test_access_key'])) {
            $data['access_key'] = $data['test_access_key'];
        }

        $result = [];
        if (empty($data['access_key'])) {
            $result = [
                'error' => 1,
                'message' => 'Incomplete EasyPost User Authentication configuration',
            ];
            return $result;
        }

        \EasyPost\EasyPost::setApiKey($data['access_key']);

        $cart = $this->Sellvana_Sales_Model_Cart->sessionCart();

        $data['name'] = 'John Doe';
        if ($cart->get('shipping_firstname') && $cart->get('shipping_lastname')) {
            $data['name'] = $cart->get('shipping_firstname') . ' ' . $cart->get('shipping_lastname');
        }

        $rates = [];
        try {
            $rates = $this->getRates($data, $cart);
            if (!empty($rates['error'])) {
                return $rates;
            }
        } catch (Exception $e) {
            $result = [
                'error' => 1,
                'message' => $e->getMessage(),
            ];
        }

        if (count($rates)) {
            foreach ($rates as $rate) {
                if (!isset($result['rates'][$rate->carrier])) {
                    $result['rates'][$rate->carrier] = [];
                }

                $result['rates'][$rate->carrier]['_' . $rate->service] = [
                    'id' => $rate->id,
                    'shipment_id' => $rate->shipment_id,
                    'price' => $rate->rate,
                ];
            }
        } else {
            $result = [
                'error' => 1,
                'message' => 'No rates for one of the packages',
            ];
            return $result;
        }

        if (!isset($result['error'])) {
            $result['success'] = 1;
        }

        return $result;
    }

    /**
     * @param array $data
     * @return array
     */
    public function getRates($data)
    {
        $shipmentData = $this->_prepareShipmentData($data);
        if (!empty($shipmentData['error'])) {
            return $shipmentData;
        }
        if ($data['from_country'] !== $data['to_country']) {
            $shipmentData = array_merge($shipmentData, $this->_prepareCustomsData($data));
        }
        $shipment = \EasyPost\Shipment::create($shipmentData);
        $rates = \EasyPost\Rate::create($shipment);

        return $rates;
    }

    protected function _prepareShipmentData($data)
    {
        $weight = $data['weight'];

        // unit conversion
        // TODO: we should probably make some unit conversion method in the product?
        $catalogConfig = $this->BConfig->get('modules/Sellvana_Catalog');
        if ($catalogConfig['weight_unit'] === 'kg') {
            $weight *= 35.274;
        } elseif ($catalogConfig['weight_unit'] === 'lb') {
            $weight *= 16;
        }
        $weight = round($weight, 1);

        $dimensions = explode('x', $data['package_size']);
        if (count($dimensions) !== 3) {
            $result = [
                'error' => 1,
                'message' => 'Dimensions in wrong format (Product ID: ' . array_shift($data['items']) . ')',
            ];
            return $result;
        }

        foreach ($dimensions as &$size) {
            if ($catalogConfig['length_unit'] === 'cm') {
                $size *= 0.3937;
            }
            $size = round($size, 1);
        }
        unset($size);

        return [
            'to_address' => array(
                'name' => $data['name'],
                'street1' => $data['to_street1'],
                'street2' => $data['to_street2'],
                'city' => $data['to_city'],
                'state' => $data['to_region'],
                'zip' => $data['to_postcode'],
                'country' => $data['to_country'],
                'phone' => $data['to_phone'],
                'email' => $data['to_email'],
            ),
            'from_address' => array(
                'name' => $data['from_name'],
                'street1' => $data['from_street1'],
                'street2' => $data['from_street2'],
                'city' => $data['from_city'],
                'state' => $data['from_region'],
                'zip' => $data['from_postcode'],
                'country' => $data['from_country'],
                'phone' => $data['from_phone'],
                'email' => $data['from_email'],
            ),
            'parcel' => array(
                'length' => $dimensions[0],
                'width' => $dimensions[1],
                'height' => $dimensions[2],
                'weight' => $weight
            )
        ];
    }

    protected function _prepareCustomsData($data)
    {
        $cart = $this->Sellvana_Sales_Model_Cart->sessionCart();
        $items = $cart->items();
        $packageSum = 0;
        $customsItems = [];

        $rate = 1;
        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiCurrency')) {
            $rate = $this->Sellvana_MultiCurrency_Main->getRate('USD');
        }

        foreach ($items as $item) {
            if (in_array($item->id, array_keys($data['items']))) {
                $packageSum += $item->row_total;
                $inventory = $item->getProduct()->getInventoryModel();
                $hsNumber = $inventory->get('hs_tariff_number');
                $hsNumber = substr(str_replace('.', '', $hsNumber), 0, 6);
                $itemValue = $item->get('row_total') + $item->get('row_tax');
                $customsItems[] = [
                    'description' => $item->get('product_name'),
                    'quantity' => $item->getQty(),
                    'weight' => $inventory->get('shipping_weight') * $item->getQty(),
                    'value' => $itemValue * $rate,
                    'hs_tariff_number' => $hsNumber,
                    'origin_country' => $inventory->get('origin_country')
                ];
            }
        }

        $customsData = [
            'customs_info' => [
                'customs_certify' => false,
                'contents_type' => 'merchandise',
                'eel_pfc' => 'NOEEI 30.37(a)',
                'customs_items' => $customsItems
            ]
        ];

        return $customsData;
    }

    protected function _itemCanBeAddedToPackage($package, $item, $qty)
    {
        if (!parent::_itemCanBeAddedToPackage($package, $item, $qty)) {
            return false;
        }

        $config = $this->BConfig->get($this->_configPath);
        $data = array_merge($config, $package);
        $data = $this->_applyDefaultPackageConfig($data);

        if ($data['from_country'] === $data['to_country']) {
            return true;
        }

        $maxIntlPackageTotal = 2500; // USD

        $rate = 1;
        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiCurrency')) {
            $rate = $this->Sellvana_MultiCurrency_Main->getRate('USD');
        }

        $itemTotal = ($item->get('row_total') + $item->get('row_tax')) / $item->get('qty');
        $potentialTotal = ($package['total'] + ($itemTotal * $qty)) * $rate;

        return ($potentialTotal <= $maxIntlPackageTotal);
    }

    public function fetchCartRates($cart = null)
    {
        if (!$cart) {
            $cart = $this->Sellvana_Sales_Model_Cart->sessionCart();
        }

        $packages = $this->calcCartPackages($cart);
        $ratedCarriers = $this->getServicesSelected();

        $cartRates = [];
        foreach ($packages as $package) {
            $package['services'] = array_keys($ratedCarriers);
            $packageRates = $this->fetchPackageRates($package);
            if (!empty($packageRates['error'])) {
                return $packageRates; // if for any package there's an error, return immediately
            }
            foreach ($ratedCarriers as $carrierId => $carrierLabel) {
                if (empty($packageRates['rates'][$carrierId])) {
                    unset($ratedCarriers[$carrierId], $cartRates[$carrierId]);
                    continue;
                }

                foreach ($packageRates['rates'][$carrierId] as $service => $rate) {
                    $code = $carrierId . ' ' . $service;

                    if (empty($cartRates[$code])) {
                        $cartRates[$code] = [
                            'packages' => [],
                            'price' => 0,
                            'weight' => 0,
                            'max_days' => 0,
                        ];
                    }

                    $packageRate = $rate;
                    $packageRate['items'] = $package['items'];
                    $cartRates[$code]['packages'][] = $packageRate;
                    $cartRates[$code]['weight'] += $package['weight'];
                    $cartRates[$code]['price'] += $rate['price'];
                    if (!empty($rate['max_days'])) {
                        $cartRates[$code]['max_days'] = max($cartRates[$code]['max_days'], $rate['max_days']);
                    }
                }
            }
        }

        uasort($cartRates, function($a, $b) {
            return $a['price'] < $b['price'] ? -1 : ($a['price'] > $b['price'] ? 1 : 0);
        });

        return $cartRates;
    }

    public function getServices(array $args = [])
    {
        $config = $this->BConfig->get($this->_configPath);
        $services = [];
        if (empty($config['access_key']) || !empty($args['no_remote'])) {
            return [];
        }

        // Carrier API is available only in production mode, so we must always use production access key
        try {
            \EasyPost\EasyPost::setApiKey($config['access_key']);
            $accounts = \EasyPost\CarrierAccount::all();
            foreach ($accounts as $account) {
                $services['_' . $account->readable] = $account->readable;
            }
        } catch (Exception $e) {
            $services = ['_' => 'Error: ' . $e->getMessage()];
        }

        return $services;
    }


}
