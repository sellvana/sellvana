<?php

/**
 * Class Sellvana_Sales_Method_Shipping_Abstract
 *
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 */
abstract class Sellvana_Sales_Method_Shipping_Abstract extends BClass implements
    Sellvana_Sales_Method_Shipping_Interface
{
    protected $_code;
    protected $_name;
    protected $_configPath;
    protected $_config;
    protected $_lastError;

    public function getCode()
    {
        return $this->_code;
    }

    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->_lastError;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        $label = $this->getConfig('label');
        return $label ?: $this->_name;
    }

    public function getSortOrder()
    {
        return $this->getConfig('sort_order');
    }

    public function getConfig($key = null, $default = null)
    {
        if (!$this->_config) {
            $this->_config = $this->BConfig->get($this->_configPath);
        }
        return null === $key ? $this->_config : (!empty($this->_config[$key]) ? $this->_config[$key] : $default);
    }

    public function getService($serviceKey)
    {
        $services = $this->getServices();
        if (!empty($services[$serviceKey])) {
            return $services[$serviceKey];
        }
        return $serviceKey;
    }

    public function getServices()
    {
        return [];
    }

    public function getConditionallyFreeServices()
    {
        return $this->getConfig('conditionally_free_services', []);
    }

    public function getServicesSelected()
    {
        $allServices = $this->getServices();
        $enabled = $this->getConfig('services');
        if (!$enabled) {
            return $allServices;
        }
        $services = [];
        foreach ($enabled as $svc) {
            if ($svc[0] !== '_') {
                $svc = '_' . $svc;
            }
            $services[$svc] = (!empty($allServices[$svc])) ? $allServices[$svc] : $svc;
        }
        return $services;
    }

    public function fetchCartRates($cart = null)
    {
        if (!$cart) {
            $cart = $this->Sellvana_Sales_Model_Cart->sessionCart();
        }
        $packages = $this->calcCartPackages($cart);
        $ratedServices = $this->getServicesSelected();

        $cartRates = [];
        foreach ($packages as $package) {
            $package['services'] = array_keys($ratedServices);
            $packageRatesResult = $this->fetchPackageRates($package);
            if (!empty($packageRatesResult['error'])) {
                return $packageRatesResult; // if for any package there's an error, return immediately
            }
            $packageRates = [];
            foreach ($packageRatesResult['rates'] as $code => $rate) {
                if ($code[0] !== '_') {
                    $code = '_' . $code;
                }
                $packageRates['rates'][$code] = $rate;
            }
            foreach ($ratedServices as $code => $label) {
                if (empty($packageRates['rates'][$code])) {
                    unset($ratedServices[$code], $cartRates[$code]);
                    continue;
                }
                if (empty($cartRates[$code])) {
                    $cartRates[$code] = [
                        'packages' => [],
                        'price' => 0,
                        'weight' => 0,
                        'max_days' => 0,
                        'exact_time' => null,
                    ];
                }
                $packageRate = $packageRates['rates'][$code];
                $packageRate['items'] = $package['items'];
                $cartRates[$code]['packages'][] = $packageRate;
                $cartRates[$code]['weight'] += $package['weight'];
                $cartRates[$code]['price'] += $packageRates['rates'][$code]['price'];
                if (!empty($packageRates['rates'][$code]['max_days'])) {
                    $cartRates[$code]['max_days'] = max($cartRates[$code]['max_days'], $packageRates['rates'][$code]['max_days']);
                }
                if (!empty($packageRates['rates'][$code]['exact_time'])) {
                    $cartRates[$code]['exact_time'] = $packageRates['rates'][$code]['exact_time'];
                }
            }
        }

        uasort($cartRates, function($a, $b) {
            return $a['price'] < $b['price'] ? -1 : ($a['price'] > $b['price'] ? 1 : 0);
        });

        return $cartRates;
    }

    /**
     * @param Sellvana_Sales_Model_Cart $cart
     * @return array
     */
    public function calcCartPackages(Sellvana_Sales_Model_Cart $cart)
    {
        $packages = [];
        $pkgIdx = 0;
        $pkgTpl = $this->_getPackageTemplate($cart);

        foreach ($cart->items() as $item) {
            $qty = $item->get('qty');
            if ($item->get('pack_separate')) {
                for ($i = 0; $i < $qty; $i++) {
                    $pkg = array_merge($pkgTpl, [
                        'qty' => 1,
                        'weight' => $item->get('shipping_weight'),
                        'total' => ($item->get('row_total') + $item->get('row_tax')) / $qty,
                        'items' => [$item->id() => 1],
                    ]);
                    $packages[$pkgIdx] = $pkg;
                    $pkgIdx++;
                }
                continue;
            }

            $rowWeight = $rowTotal = 0;
            $packageQty = 0;
            if (empty($packages[$pkgIdx])) {
                $packages[$pkgIdx] = array_merge($pkgTpl, ['qty' => 0, 'weight' => 0, 'total' => 0, 'items' => []]);
            }
            for ($i = 0; $i < $qty; $i++) {
                if (!empty($packages[$pkgIdx]) && !$this->_itemCanBeAddedToPackage($packages[$pkgIdx], $item, $packageQty+1)) {
                    if ($packageQty > 0) {
                        $packages[$pkgIdx]['items'][$item->id()] = $packageQty;
                        $packages[$pkgIdx]['qty'] += $packageQty;
                        $packages[$pkgIdx]['weight'] += $rowWeight;
                        $packages[$pkgIdx]['total'] += $rowTotal;
                    }

                    $pkgIdx++;
                    $rowWeight = $rowTotal = 0;
                    $packageQty = 0;

                    if (empty($packages[$pkgIdx])) {
                        $packages[$pkgIdx] = array_merge($pkgTpl, ['qty' => 0, 'weight' => 0, 'total' => 0, 'items' => []]);
                    }
                }

                $packageQty++;
                $rowWeight += $item->get('shipping_weight');
                $rowTotal += ($item->get('row_total') + $item->get('row_tax')) / $qty;
            }

            if ($packageQty > 0) {
                $packages[$pkgIdx]['items'][$item->id()] = $packageQty;
                $packages[$pkgIdx]['qty'] += $packageQty;
                $packages[$pkgIdx]['weight'] += $rowWeight;
                $packages[$pkgIdx]['total'] += $rowTotal;
            }
        }

        return $packages;
    }

    /**
     * @param array $package
     * @param Sellvana_Sales_Model_Cart_Item $item
     * @param int $qty
     * @return bool
     */
    protected function _itemCanBeAddedToPackage($package, $item, $qty)
    {
        $maxPkgWeight = $this->getConfig('max_package_weight', 1000);
        $rowWeight = $qty * $item->get('shipping_weight');
        return (($package['weight'] + $rowWeight) <= $maxPkgWeight);
    }

    public function fetchPackageRates($package)
    {
        return $this->_fetchRates($package);
    }

    /**
     * @param Sellvana_Sales_Model_Cart $cart
     * @return array
     */
    protected function _getPackageTemplate(Sellvana_Sales_Model_Cart $cart)
    {
        return [
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
    }

    protected function _applyDefaultPackageConfig($data)
    {
        $config = $this->BConfig->get('modules/Sellvana_Sales');

        if (empty($data['to_country'])) {
            $data['to_country'] = !empty($config['store_country']) ? $config['store_country'] : null;
        }
        if (empty($data['from_name'])) {
            $data['from_name'] =  !empty($config['store_name']) ? $config['store_name'] : null;
        }
        if (empty($data['from_email'])) {
            $data['from_email'] =  !empty($config['store_email']) ? $config['store_email'] : null;
        }
        if (empty($data['from_city'])) {
            $data['from_city'] =  !empty($config['store_city']) ? $config['store_city'] : null;
        }
        if (empty($data['from_region'])) {
            $data['from_region'] =  !empty($config['store_region']) ? $config['store_region'] : null;
        }
        if (empty($data['from_postcode'])) {
            $data['from_postcode'] = !empty($config['store_postcode']) ? $config['store_postcode'] : null;
        }
        if (empty($data['from_country'])) {
            $data['from_country'] =  !empty($config['store_country']) ? $config['store_country'] : null;
        }
        if (empty($data['from_street1'])) {
            $data['from_street1'] =  !empty($config['store_street1']) ? $config['store_street1'] : null;
        }
        if (empty($data['from_street2'])) {
            $data['from_street2'] =  !empty($config['store_street2']) ? $config['store_street2'] : null;
        }
        if (empty($data['from_phone'])) {
            $data['from_phone'] =  !empty($config['store_phone']) ? $config['store_phone'] : null;
        }

        if (empty($data['customer_context'])) {
            $data['customer_context'] = 'Sellvana Rates Request';
        }

        if (empty($data['services'])) {
            $data['services'] = array_keys($this->getServicesSelected());
        }

        if (empty($data['length'])) {
            $data['length'] = 10;
        }
        if (empty($data['width'])) {
            $data['width'] = 10;
        }
        if (empty($data['height'])) {
            $data['height'] = 10;
        }

        if (!isset($data['residential'])) {
            $data['residential'] = true;
        }
        return $data;
    }

    /**
     * @param array $data
     *  - weight        (required)
     *  - to_postcode   (required)
     *  - to_country
     *  - from_country
     *  - from_postcode
     *  - services
     *  - length
     *  - width
     *  - height
     *  - residential
     *
     * @return array
     *  - success
     *  - rates[]
     *      - price
     *      - days
     *  - error
     *  - message
     */
    protected function _fetchRates($data)
    {
        return ['error' => true, 'message' => 'Not implemented'];
    }

}
