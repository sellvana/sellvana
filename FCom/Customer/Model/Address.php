<?php

class FCom_Customer_Model_Address extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_customer_address';
    protected static $_origClass = __CLASS__;

    protected $_validationRules = array(
        /*array('customer_id', '@required'),
        array('email', '@required'),*/
        array('firstname', '@required'),
        array('lastname', '@required'),
        array('street1', '@required'),
        array('city', '@required'),
        array('country', '@required'),
//        array('region', '@required'),
        array('postcode', '@required'),

        array('email', '@email'),

        array('customer_id', '@integer'),
        array('lat', '@numeric'),
        array('lng', '@numeric'),
    );

    public function as_html($obj=null)
    {
        if (is_null($obj)) {
            $obj = $this;
        }
        $countries = FCom_Geo_Model_Country::i()->options();
        return '<address>'
            .'<div class="f-street-address">'.$obj->street1.'</div>'
            .($obj->street2 ? '<div class="f-extended-address">'.$obj->street2.'</div>' : '')
            .($obj->street3 ? '<div class="f-extended-address">'.$obj->street3.'</div>' : '')
            .'<span class="f-city">'.$obj->city.'</span>, '
            .'<span class="f-region">'.$obj->region.'</span> '
            .'<span class="f-postal-code">'.$obj->postcode.'</span>'
            .'<div class="f-country-name">'.(!empty($countries[$obj->country]) ? $countries[$obj->country] : $obj->country).'</div>'
            .'</address>';

    }

    public function onBeforeDelete() {
        if (!parent::onBeforeDelete()) return false;

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
                'region'            => $address->region,
                'postcode'          => $address->postcode,
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
        if (!empty($post['region'])) {
            $data['region'] = $post['region'];
        }
        if (!empty($post['postcode'])) {
            $data['postcode'] = $post['postcode'];
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

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;
        if (!$this->create_at) $this->create_at = BDb::now();
        $this->update_at = BDb::now();
        return true;
    }

    public function newShipping($address, $customer)
    {
        $data = array('address' => $address);
        static::import($data, $customer, 'shipping');
    }

    public function newBilling($address, $customer)
    {
        $data = array('address' => $address);
        static::import($data, $customer, 'billing');
    }

    public static function import($data, $cust, $atype='billing')
    {
        $addr = static::create(array('customer_id' => $cust->id));

        if(!empty($data['address'])){
            $addr->set($data['address']);
        }
        $addr->save();

        if (!empty($data['address']['default_billing'])) {
            $atype = 'billing';
        }

        if (!empty($data['address']['default_shipping'])) {
            $atype = 'shipping';
        }

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
