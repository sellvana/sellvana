<?php

class FCom_Customer_Model_Address extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_customer_address';
    protected static $_origClass = __CLASS__;

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

    public function beforeDelete() {
        if (!parent::beforeDelete()) return false;

        $customer = $this->relatedModel("FCom_Customer_Model_Customer", $this->customer_id);

        if ($this->id == $customer->default_shipping_id) {
            $customer->default_shipping_id = null;
            $customer->save();
        }
        if ($this->id == $customer->default_billing_id) {
            $customer->default_billing_id = null;
            $customer->save();
        }

        return $this;
    }

    public function prepareApiData($customerAddress)
    {
        $result = array();
        foreach($customerAddress as $address) {
            $result[] = array(
                'id' => $address->id,
                'customer_id'       => $address->customer_id,
                'firstname'         => $address->firstname,
                'lastname'          => $address->lastname,
                'street1'           => $address->street1,
                'street2'           => $address->street2,
                'city'              => $address->city,
                'state'             => $address->state,
                'zip'               => $address->zip,
                'country_code'      => $address->country,
                'phone'             => $address->phone,
                'fax'               => $address->fax,
                );
        }
        return $result;
    }

    public function formatApiPost($post)
    {
        $data = array();

        if (!empty($post['firstname'])) {
            $data['firstname'] = $post['firstname'];
        }
        if (!empty($post['lastname'])) {
            $data['lastname'] = $post['lastname'];
        }
        if (!empty($post['street1'])) {
            $data['street1'] = $post['street1'];
        }
        if (!empty($post['street2'])) {
            $data['street2'] = $post['street2'];
        }
        if (!empty($post['city'])) {
            $data['city'] = $post['city'];
        }
        if (!empty($post['state'])) {
            $data['state'] = $post['state'];
        }
        if (!empty($post['zip'])) {
            $data['zip'] = $post['zip'];
        }
        if (!empty($post['country_code'])) {
            $data['country'] = $post['country_code'];
        }
        if (!empty($post['phone'])) {
            $data['phone'] = $post['phone'];
        }
        if (!empty($post['fax'])) {
            $data['fax'] = $post['fax'];
        }
        return $data;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (!$this->create_dt) $this->create_dt = BDb::now();
        $this->update_dt = BDb::now();
        return true;
    }

    public function newShipping($address, $customer)
    {
        $data = array('address' => $address);
        self::import($data, $customer, 'shipping');
    }

    public function newBilling($address, $customer)
    {
        $data = array('address' => $address);
        self::import($data, $customer, 'billing');
    }

    public static function import($data, $cust, $atype='billing')
    {
        $addr = static::create(array('customer_id' => $cust->id));

        if(!empty($data['address'])){
            $addr->set($data['address']);
        }
        $addr->save();

        if (!$cust->default_billing_id && 'billing' == $atype) {
            $cust->set('default_billing_id', $addr->id);
        }
        if (!$cust->default_shipping_id && 'shipping' == $atype) {
            $cust->set('default_shipping_id', $addr->id);
        }

        if ($cust->is_dirty()) {
            $cust->save();
        }

        return $addr;
    }
}