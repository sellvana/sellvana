<?php

class FCom_Sales_Model_OrderAddress extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_address';
    protected static $_origClass = __CLASS__;

    public function findByOrder($orderId, $atype='shipping')
    {
        return self::i()->orm()->where("order_id",$orderId)->where('atype', $atype)->find_one();
    }

    public static function as_html($obj=null)
    {
        if (is_null($obj)) {
            $obj = $this;
        }
        $countries = FCom_Geo_Model_Country::i()->options();
        return '<div class="adr">'
            .'<div class="street-address">'.$obj->street1.'</div>'
            .($obj->street2 ? '<div class="extended-address">'.$obj->street2.'</div>' : '')
            .($obj->street3 ? '<div class="extended-address">'.$obj->street3.'</div>' : '')
            .'<span class="locality">'.$obj->city.'</span>, '
            .'<span class="region">'.$obj->region.'</span> '
            .'<span class="postal-code">'.$obj->postcode.'</span>'
            .'<div class="country-name">'.(!empty($countries[$obj->country]) ? $countries[$obj->country] : $obj->country).'</div>'
            .'</div>';

    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (!$this->create_dt) $this->create_dt = BDb::now();
        $this->update_dt = BDb::now();
        return true;
    }

    public function newAddress($orderId, $newAddress)
    {
        if (is_object($newAddress)) {
            $data = $newAddress->as_array();
        } else {
            $data = $newAddress;
        }
        if (isset($data['id'])){
            unset($data['id']);
        }
        if (empty($data['atype'])) {
            $data['atype'] = 'shipping';
        }

        $address = $data;
        $address['order_id'] = $orderId;

        $newAddress = $this->findByOrder($orderId, $address['atype']);
        if (!$newAddress) {
            $newAddress = self::create($address);
        } else {
            $newAddress->set($address);
        }
        $newAddress->save();
        return $newAddress;
    }
}