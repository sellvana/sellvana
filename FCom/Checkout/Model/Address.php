<?php

class FCom_Checkout_Model_Address extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_checkout_address';
    protected static $_origClass = __CLASS__;

    public function findByCartType($cartId, $atype)
    {
        return FCom_Checkout_Model_Address::i()->orm()->where("cart_id",$cartId)->where('atype', $atype)->find_one();
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
            .'<span class="region">'.$obj->state.'</span> '
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

    public function newShipping($cartId, $userData)
    {
        $this->newAddress($cartId, 'shipping', $userData->as_array());
    }

    public function newBilling($cartId, $userData, $email)
    {
        $this->newAddress($cartId, 'billing', $userData->as_array(), $email);
    }

    public function newAddress($cartId, $atype, $userData, $email = '')
    {
        $address = array(
            'cart_id' => $cartId,
            'email' => $email,
            'atype' => $atype,
            'firstname' => $userData['firstname'],
            'lastname' => $userData['lastname'],
            'attn' => $userData['attn'],
            'street1' => $userData['street1'],
            'street2' => $userData['street2'],
            'street3' => $userData['street3'],
            'city' => $userData['city'],
            'state' => $userData['state'],
            'zip' => $userData['zip'],
            'country' => $userData['country'],
            'phone' => $userData['phone'],
            'fax' => $userData['fax'],
            'lat' => $userData['lat'],
            'lng' => $userData['lng'],
            'created_dt' => null,
            'updated_dt' => null
        );
        $newAddress = $this->findByCartType($cartId, $atype);
        if (!$newAddress) {
            $newAddress = FCom_Checkout_Model_Address::create($address);
        } else {
            $newAddress->set($address);
        }
        $newAddress->save();
        return $newAddress;
    }
}