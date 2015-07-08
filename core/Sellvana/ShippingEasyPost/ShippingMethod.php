<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_ShippingUps_ShippingMethod
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
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
        $config = $this->BConfig->get($this->_configPath);
        $data = array_merge($config, $data);

        $data = $this->_applyDefaultPackageConfig($data);

        if ($data['mode'] === self::MODE_TEST) {
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

        \EasyPost\EasyPost::setApiKey($data['test_access_key']);

        $cart = $this->Sellvana_Sales_Model_Cart->sessionCart();

        $name = 'John Doe';
        if ($cart->get('shipping_firstname') && $cart->get('shipping_lastname')) {
            $name = $cart->get('shipping_firstname') . ' ' . $cart->get('shipping_lastname');
        }

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
        $dimensions = explode('x', $data['size']);
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

        $shipment = \EasyPost\Shipment::create(array(
            'to_address' => array(
                'name' => $name,
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
                'width' => $dimensions[0],
                'length' => $dimensions[1],
                'height' => $dimensions[2],
                'weight' => $weight
            )
        ));

        $rates = \EasyPost\Rate::create($shipment);

        foreach ($rates as $rate) {
            if (!isset($result['rates'][$rate->carrier])) {
                $result['rates'][$rate->carrier] = [];
            }

            $result['rates'][$rate->carrier][$rate->service] = [
                'price' => $rate->rate,
            ];
        }

        if (!isset($result['error'])) {
            $result['success'] = 1;
        }

        return $result;
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

    public function calcCartPackages($cart)
    {
        $packages = [];
        $pkgIdx = 0;
        $pkgTpl = [
            'customer_context' => $cart->id(),
            'to_street1' => $cart->get('shipping_street1'),
            'to_street2' => $cart->get('shipping_street2'),
            'to_city' => $cart->get('shipping_city'),
            'to_region' => $cart->get('shipping_region'),
            'to_postcode' => $cart->get('shipping_postcode'),
            'to_country' => $cart->get('shipping_country'),
            'to_phone' => $cart->get('shipping_phone'),
            'to_email' => $cart->get('customer_email'),
        ];

        foreach ($cart->items() as $item) {
            $qty = $item->get('qty');
            $weight = $item->get('shipping_weight');
            $size = $item->get('shipping_size');
            for ($i = 0; $i < $qty; $i++) {
                $pkg = array_merge($pkgTpl, [
                    'qty' => 1,
                    'weight' => $weight,
                    'size' => $size,
                    'items' => [$item->id() => 1],
                ]);
                $packages[$pkgIdx] = $pkg;
                $pkgIdx++;
            }
        }
        return $packages;
    }

    public function getServices()
    {
        $config = $this->BConfig->get($this->_configPath);
        $services = [];

        // Carrier API is available only in production mode, so we must always use production access key
        \EasyPost\EasyPost::setApiKey($config['access_key']);
        $accounts = \EasyPost\CarrierAccount::all();
        foreach ($accounts as $account) {
            $services[$account->readable] = $account->readable;
        }

        return $services;
    }


}
