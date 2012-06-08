<?php

class FCom_Checkout_Model_Countries extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_countries';
    protected static $_origClass = __CLASS__;

    public function getList()
    {
        return array();
        $countries = $this->orm()->find_many();
        $countriesArray = array();
        foreach($countries as $country) {
            $countriesArray[] = array('id' => $country->id(), 'text' => $country->country_name);
        }
        return $countriesArray;
    }
}