<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Customer_Import extends BImport
{
    protected $fields = [
            'customer.firstname' => ['pattern' => 'first.*name'],
            'customer.lastname' => ['pattern' => 'last.*name'],
            'customer.email' => ['pattern' => 'e[ -]?mail'],
            'customer.password' => ['pattern' => 'pass[ -]?(word|phrase)'],
            'address.default_billing' => ['pattern' => '(default_billing)'],
            'address.default_shipping' => ['pattern' => '(default_shipping)'],
            'address.firstname' => ['pattern' => '(firstname)'],
            'address.lastname' => ['pattern' => '(lastname)'],
            'address.street1' => ['pattern' => '(street)'],
            'address.street2' => ['pattern' => '(address|street) ?2'],
            'address.street3' => ['pattern' => '(address|street) ?3'],
            'address.city' => ['pattern' => 'city'],
            'address.region' => ['pattern' => '(state|province|region)'],
            'address.postcode' => ['pattern' => '(zip|post(al)?([ -]?code)?)'],
            'address.country' => ['pattern' => 'country'],
            'address.phone' => ['pattern' => '(tele)?phone'],
            'address.fax' => ['pattern' => 'fax'],
        ];

    protected $dir = 'customers';
    protected $model = 'FCom_Customer_Model_Customer';
}