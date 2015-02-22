<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Method_Shipping_Abstract
 *
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 */
abstract class FCom_Sales_Method_Shipping_Abstract extends BClass implements
    FCom_Sales_Method_Shipping_Interface
{
    protected $_code;
    protected $_name;
    protected $_configPath;
    protected $_config;
    protected $_lastError;

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
        return false;
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
            $services[$svc] = $allServices[$svc];
        }
        return $services;
    }

    public function fetchCartRates($cart = null)
    {
        if (!$cart) {
            $cart = $this->FCom_Sales_Model_Cart->sessionCart();
        }
        $packages = $this->calcCartPackages($cart);
        $ratedServices = $this->getServicesSelected();

        $cartRates = [];
        foreach ($packages as $package) {
            $package['services'] = array_keys($ratedServices);
            $packageRates = $this->fetchPackageRates($package);
            if (!empty($packageRates['error'])) {
                return $packageRates; // if for any package there's an error, return immediately
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
            }
        }

        uasort($cartRates, function($a, $b) {
            return $a['price'] < $b['price'] ? -1 : ($a['price'] > $b['price'] ? 1 : 0);
        });

        return $cartRates;
    }

    public function calcCartPackages($cart)
    {
        $maxPkgWeight = $this->getConfig('max_package_weight', 1000);

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
        ];

        foreach ($cart->items() as $item) {
            $qty = $item->get('qty');
            if ($item->get('pack_separate')) {
                for ($i = 0; $i < $qty; $i++) {
                    $pkg = array_merge($pkgTpl, [
                        'qty' => 1,
                        'weight' => $item->get('shipping_weight'),
                        'items' => [$item->id() => 1],
                    ]);
                    $packages[$pkgIdx] = $pkg;
                    $pkgIdx++;
                }
                continue;
            }
            $rowWeight = $qty * $item->get('shipping_weight');
            if (!empty($packages[$pkgIdx]) && ($packages[$pkgIdx]['weight'] + $rowWeight) > $maxPkgWeight) {
                $pkgIdx++;
            }
            if (empty($packages[$pkgIdx])) {
                $packages[$pkgIdx] = array_merge($pkgTpl, ['qty' => 0, 'weight' => 0, 'items' => []]);
            }
            $packages[$pkgIdx]['items'][$item->id()] = $qty;
            $packages[$pkgIdx]['qty'] += $qty;
            $packages[$pkgIdx]['weight'] += $rowWeight;
        }
        return $packages;
    }

    public function fetchPackageRates($package)
    {
        return $this->_fetchRates($package);
    }

    protected function _applyDefaultPackageConfig($data)
    {
        $config = $this->BConfig->get('modules/FCom_Sales');

        if (empty($data['from_country'])) {
            $data['from_country'] =  !empty($config['store_country']) ? $config['store_country'] : null;
        }
        if (empty($data['from_postcode'])) {
            $data['from_postcode'] = !empty($config['store_postcode']) ? $config['store_postcode'] : null;
        }
        if (empty($data['to_country'])) {
            $data['to_country'] = !empty($config['store_country']) ? $config['store_country'] : null;
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
