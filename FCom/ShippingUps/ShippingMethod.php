<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_ShippingUps_ShippingMethod
 *
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 */
class FCom_ShippingUps_ShippingMethod extends FCom_Sales_Method_Shipping_Abstract
{
    protected $_name = 'Universal post service';
    protected $_code = 'ShippingUps';
    /**
     * @var UpsRate
     */
    protected $_rate;

    public function init()
    {
    }

    /**
     * @param $shipNumber
     * @param $tozip
     * @param $service
     * @param $length
     * @param $width
     * @param $height
     * @param $weight
     * @return bool
     */
    protected function _rateApiCall($shipNumber, $tozip, $service, $length, $width, $height, $weight)
    {
        include_once __DIR__ . '/lib/UpsRate.php';

        $config = $this->BConfig->get('modules/FCom_ShippingUps');
        $password = !empty($config['password']) ? $config['password'] : '';
        $account = !empty($config['account']) ? $config['account'] : '';
        $accessKey = !empty($config['access_key']) ? $config['access_key'] : '';
        $rateApiUrl = !empty($config['rate_api_url']) ? $config['rate_api_url'] : '';

        //todo: notify if fromzip is not set
        $fromzip = $this->BConfig->get('modules/FCom_Checkout/store_zip');

        if (empty($accessKey) || empty($account) || empty($password)) {
            return false;
        }

        $this->_rate = new UpsRate($rateApiUrl);
        $this->_rate->setUpsParams($accessKey, $account, $password, $shipNumber);
        $this->_rate->getRate($fromzip, $tozip, $service, $length, $width, $height, $weight);
        return true;
    }

    /**
     * @return string
     */
    public function getEstimate()
    {
        if (!$this->_rate) {
            $cart = $this->FCom_Sales_Model_Cart->sessionCart();
            $this->getRateCallback($cart);
            if (!$this->_rate) {
                return 'Unable to calculate';
            }
        }
        $estimate = $this->_rate->getEstimate();
        if (!$estimate) {
            return 'Unable to calculate';
        }
        $days = ($estimate == 1) ? ' day' : ' days';
        return $estimate . $days;
    }

    /**
     * UPS services
     * @return array
     */
    public function getServices()
    {
        return [
            '01' => 'UPS Next Day Air',
            '02' => 'UPS Second Day Air',
            '03' => 'UPS Ground',
            '07' => 'UPS Worldwide Express',
            '08' => 'UPS Worldwide Expedited',
            '11' => 'UPS Standard',
            '12' => 'UPS Three-Day Select',
            '13' => 'Next Day Air Saver',
            '14' => 'UPS Next Day Air Early AM',
            '54' => 'UPS Worldwide Express Plus',
            '59' => 'UPS Second Day Air AM',
            '65' => 'UPS Saver'
        ];
    }

    /**
     * @return array
     */
    public function getDefaultService()
    {
        return ['03' => 'UPS Ground'];
    }

    /**
     * @return array
     */
    public function getServicesSelected()
    {
        $c = $this->BConfig;
        $selected = [];
        foreach ($this->getServices() as $sId => $sName) {
            if ($c->get('modules/FCom_ShippingUps/services/s' . $sId) == 1) {
                $selected[$sId] = $sName;
            }
        }
        if (empty($selected)) {
            $selected = $this->getDefaultService();
        }
        return $selected;
    }

    /**
     * @param FCom_Sales_Model_Cart $cart
     * @return int
     */
    public function getRateCallback($cart)
    {
        //address
        $user = $this->FCom_Customer_Model_Customer->sessionUser();
        $shippingAddress = $cart->getShippingAddress();
        if ($user && !$shippingAddress) {
            $shippingAddress = $user->defaultShipping();
        }
        $tozip = $shippingAddress->postcode;
        //service
        if ($cart->shipping_method == $this->_code) {
            $service = $cart->shipping_service;
        } else {
            $service = '01';
        }
        //package dimension
        $items = $cart->items();
        $length = $width = $height = 10;
        $packages = [];
        $packageId = 0;
        $groupPackageId = 0;
        foreach ($items as $item) {
            $itemWeight = $item->getItemWeight();
            if ($itemWeight > 250 ||  $itemWeight == 0) {
                continue;
            }
            for ($i = 0; $i < $item->getQty(); $i++) {
                if ($item->isGroupable()) {
                    if (!empty($packages[$groupPackageId]) && $itemWeight + $packages[$groupPackageId] >= 150) {
                        $packageId++;
                        $groupPackageId = $packageId;
                    }
                    if (!empty($packages[$groupPackageId])) {
                        $packages[$groupPackageId] += $itemWeight;
                    } else {
                        $packages[$groupPackageId] = $itemWeight;
                    }

                } else {
                    $packageId++;
                    $packages[$packageId] = $itemWeight;
                }
            }
        }
        //package weight
        $total = 0;
        foreach($packages as $pack) {
            // Returns false if no credentials are configured.
            // As a side effect, $this->_rate will be NULL.
            if ($this->_rateApiCall($cart->id(), $tozip, $service, $length,
                $width, $height, $pack)) {
                if ($this->_rate->isError()) {
                     continue;
                }
                $total += $this->_rate->getTotal();
            }
        }
        return $total;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->_rate->getError();
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->_name;
    }
}
