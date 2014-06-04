<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Address extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_address';
    protected static $_origClass = __CLASS__;

    public function findByOrder($orderId, $atype = 'shipping')
    {
        return $this->orm()->where("order_id", $orderId)->where('atype', $atype)->find_one();
    }

    public function as_html($obj = null)
    {
        if (is_null($obj)) {
            $obj = $this;
        }
        $countries = $this->FCom_Geo_Model_Country->options();
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
