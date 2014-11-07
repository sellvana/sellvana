<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Order_Address
 *
 * @property int $id
 * @property int $order_id
 * @property string $atype enum('shipping','billing')
 * @property string $firstname
 * @property string $lastname
 * @property string $middle_initial
 * @property string $prefix
 * @property string $suffix
 * @property string $company
 * @property string $attn
 * @property string $street1
 * @property string $street2
 * @property string $street3
 * @property string $city
 * @property string $region
 * @property string $postcode
 * @property string $country
 * @property string $phone
 * @property string $fax
 * @property datetime $create_at
 * @property datetime $update_at
 * @property float $lat
 * @property float $lng
 */
class FCom_Sales_Model_Order_Address extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_address';
    protected static $_origClass = __CLASS__;

    /**
     * @param $orderId
     * @param string $atype
     * @return FCom_Sales_Model_Order_Address
     */
    public function findByOrder($orderId, $atype = 'shipping')
    {
        return $this->orm()->where("order_id", $orderId)->where('atype', $atype)->find_one();
    }

    /**
     * @param null $obj
     * @return string
     * @throws BException
     */
    public function as_html($obj = null)
    {
        if (is_null($obj)) {
            $obj = $this;
        }
        $countries = $this->BLocale->getAvailableCountries();
        return '<div class="adr">'
            . '<div class="street-address">' . $obj->street1 . '</div>'
            . ($obj->street2 ? '<div class="extended-address">' . $obj->street2 . '</div>' : '')
            . ($obj->street3 ? '<div class="extended-address">' . $obj->street3 . '</div>' : '')
            . '<span class="locality">' . $obj->city . '</span>, '
            . '<span class="region">' . $obj->region . '</span> '
            . '<span class="postal-code">' . $obj->postcode . '</span>'
            . '<div class="country-name">' . (!empty($countries[$obj->country]) ? $countries[$obj->country] : $obj->country) . '</div>'
            . '</div>';

    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;
        if (!$this->create_at) $this->create_at = $this->BDb->now();
        $this->update_at = $this->BDb->now();
        return true;
    }

    /**
     * @param $orderId
     * @param $newAddress
     * @return BModel|static
     * @throws BException
     */
    public function newAddress($orderId, $newAddress)
    {
        if (is_object($newAddress)) {
            $data = $newAddress->as_array();
        } else {
            $data = $newAddress;
        }
        if (isset($data['id'])) {
            unset($data['id']);
        }
        if (empty($data['atype'])) {
            $data['atype'] = 'shipping';
        }

        $address = $data;
        $address['order_id'] = $orderId;

        $newAddress = $this->findByOrder($orderId, $address['atype']);
        if (!$newAddress) {
            $newAddress = $this->create($address);
        } else {
            $newAddress->set($address);
        }
        $newAddress->save();
        return $newAddress;
    }

    /**
     * @param string $delim
     * @return string
     */
    public function getFullAddress($delim = "\n")
    {
        $addressData = [];
        $addressParts = ['street1', 'street2', 'street3',  ];
        foreach ($addressParts as $p) {
            if ($part = $this->get($p)) {
                $addressData[] = $part;
            }
        }
        return join($delim, $addressData);
    }
}
