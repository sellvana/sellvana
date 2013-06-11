<?php

class FCom_Sales_Model_Cart_Address extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_cart_address';
    protected static $_origClass = __CLASS__;

    public function as_html($obj=null)
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
}