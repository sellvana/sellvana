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

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;
        if (!$this->create_at) $this->create_at = BDb::now();
        $this->update_at = BDb::now();
        return true;
    }

    /**
     * Validate provided address data
     * Very basic validation for presence of required fields
     * @todo add element validators
     * @param array $data
     * @param bool  $breakOnFail
     * @return bool
     */
    public function validate($data, $breakOnFail = true)
    {
        $valid  = true;
        $failed = array();

        BEvents::i()->fire(__CLASS__."validate", array("failed" => &$failed, 'valid' => &$valid));
        $required = BConfig::i()->get("modules/FCom_Sales/required_address_fields");
        if(!$required){
            $required = array(
                "firstname",
                "lastname",
                "email",
                "street1",
                "city",
                "country",
                "region",
                "postcode"
            );
        }

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                if($breakOnFail){
                    return false;
                }
                $valid = false;
                $failed['required'][] = $field;
            }
        }
        if (!$valid) {
            BEvents::i()->fire(__CLASS__."validation_failed", array("failed" => $failed));
        }

        return $valid;
    }
}