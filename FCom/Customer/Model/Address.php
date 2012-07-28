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

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (!$this->create_dt) $this->create_dt = BDb::now();
        $this->update_dt = BDb::now();
        return true;
    }

    public static function import($data, $cust)
    {
        if ($cust->default_billing_id) {
            $addr = static::load($cust->default_billing_id);
            //$addr = Model::factory('FCom_Customer_Model_Address')->find_one($cust->default_billing_id);
        }
        if (empty($addr)) {
            $addr = static::create(array('customer_id' => $cust->id));
        }
        //save full contry name
        /*if (!empty($data['address']['country']) && strlen($data['address']['country'])>2) {
            $data['address']['country'] = FCom_Geo_Model_Country::i()->getIsoByName($data['address']['country']);
        }
*/
        if(!empty($data['address'])){
            $addr->set($data['address']);
        }
        $addr->save();

        if (!$cust->default_billing_id) {
            $cust->set('default_billing_id', $addr->id);
        }
        if (!$cust->default_shipping_id) {
            $cust->set('default_shipping_id', $addr->id);
        }
        $cust->save();

        return $addr;
    }

    public static function install()
    {
        $tCustomer = FCom_Customer_Model_Customer::table();
        $tAddress = static::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$tAddress} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) unsigned NOT NULL,
  `firstname` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `attn` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street1` text COLLATE utf8_unicode_ci NOT NULL,
  `street2` text COLLATE utf8_unicode_ci,
  `street3` text COLLATE utf8_unicode_ci,
  `city` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `state` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zip` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fax` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `create_dt` datetime NOT NULL,
  `update_dt` datetime NOT NULL,
  `lat` decimal(15,10) DEFAULT NULL,
  `lng` decimal(15,10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_{$tAddress}_customer` FOREIGN KEY (`customer_id`) REFERENCES {$tCustomer} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    /*
ALTER TABLE {$tCustomer}
  ADD CONSTRAINT `FK_{$tCustomer}_billing` FOREIGN KEY (`default_billing_id`) REFERENCES {$tAddress} (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_{$tCustomer}_shipping` FOREIGN KEY (`default_shipping_id`) REFERENCES {$tAddress} (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
  */
    }

    public static function upgrade_0_1_1()
    {
        try {
            BDb::run("
                ALTER TABLE ".self::table()."
                ADD COLUMN `lat` DECIMAL(15,10) NULL,
                ADD COLUMN `lng` DECIMAL(15,10) NULL;
            ");
        } catch (Exception $e) {
            //columns already exist
        }
    }
}