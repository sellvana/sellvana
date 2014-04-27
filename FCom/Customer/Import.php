<?php

class FCom_Customer_Import extends BImport
{
    protected $fields = array(
            'customer.firstname' => array( 'pattern' => 'first.*name' ),
            'customer.lastname' => array( 'pattern' => 'last.*name' ),
            'customer.email' => array( 'pattern' => 'e[ -]?mail' ),
            'customer.password' => array( 'pattern' => 'pass[ -]?(word|phrase)' ),
            'address.default_billing' => array( 'pattern' => '(default_billing)' ),
            'address.default_shipping' => array( 'pattern' => '(default_shipping)' ),
            'address.firstname' => array( 'pattern' => '(firstname)' ),
            'address.lastname' => array( 'pattern' => '(lastname)' ),
            'address.street1' => array( 'pattern' => '(street)' ),
            'address.street2' => array( 'pattern' => '(address|street) ?2' ),
            'address.street3' => array( 'pattern' => '(address|street) ?3' ),
            'address.city' => array( 'pattern' => 'city' ),
            'address.region' => array( 'pattern' => '(state|province|region)' ),
            'address.postcode' => array( 'pattern' => '(zip|post(al)?([ -]?code)?)' ),
            'address.country' => array( 'pattern' => 'country' ),
            'address.phone' => array( 'pattern' => '(tele)?phone' ),
            'address.fax' => array( 'pattern' => 'fax' ),
        );

    protected $dir = 'customers';
    protected $model = 'FCom_Customer_Model_Customer';
}