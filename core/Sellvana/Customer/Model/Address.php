<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Customer_Model_Address
 * @property int $id
 * @property int $customer_id
 * @property string $email
 * @property string $firstname
 * @property string $lastname
 * @property string $middle_initial
 * @property string $prefix
 * @property string $suffix
 * @property string $company
 * @property string $attn
 * @property string $street1
 * @property string $street2
 * @property string $street3
 * @property string $city
 * @property string $region
 * @property string $postcode
 * @property string $country
 * @property string $phone
 * @property string $fax
 * @property string $create_at
 * @property string $update_at
 * @property string $lat
 * @property string $lng
 * @property int $is_default_billing
 * @property int $is_default_shipping
 *
 * DI
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_Customer_Model_Address extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_customer_address';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['customer_id', 'street1'],
        'related'    => ['customer_id' => 'Sellvana_Customer_Model_Customer.id'],
    ];
    protected static $_validationRules = [
        /*array('customer_id', '@required'),
        array('email', '@required'),*/
        ['firstname', '@required'],
        ['lastname', '@required'],
        ['street1', '@required'],
        ['city', '@required'],
        ['country', '@required'],
//        array('region', '@required'),
        ['postcode', '@required'],

        ['email', '@email'],

        ['customer_id', '@integer'],
        ['lat', '@numeric'],
        ['lng', '@numeric'],
    ];

    protected static $fields = [
        'company',
        'attn',
        'firstname',
        'lastname',
        'street1',
        'street2',
        'city',
        'region',
        'postcode',
        'country',
        'phone',
        'fax',
    ];

    public function as_html($obj = null)
    {
        if (is_null($obj)) {
            $obj = $this;
        }
        $countries = $this->BLocale->getAvailableCountries();
        return '<address>'
            . '<div class="f-street-address">' . $obj->street1 . '</div>'
            . ($obj->street2 ? '<div class="f-extended-address">' . $obj->street2 . '</div>' : '')
            . ($obj->street3 ? '<div class="f-extended-address">' . $obj->street3 . '</div>' : '')
            . '<span class="f-city">' . $obj->city . '</span>, '
            . '<span class="f-region">' . $obj->region . '</span> '
            . '<span class="f-postal-code">' . $obj->postcode . '</span>'
            . '<div class="f-country-name">' . (!empty($countries[$obj->country]) ? $countries[$obj->country] : $obj->country) . '</div>'
            . '</address>';

    }

    public function onBeforeDelete()
    {
        if (!parent::onBeforeDelete()) return false;

        $customer = $this->relatedModel("Sellvana_Customer_Model_Customer", $this->customer_id);
        /** @type Sellvana_Customer_Model_Customer $customer */

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

    /**
     * @param $customerAddress
     * @return array
     */
    public function prepareApiData($customerAddress)
    {
        $result = [];
        foreach ($customerAddress as $address) {
            $result[] = [
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
                ];
        }
        return $result;
    }

    /**
     * @param $post
     * @return array
     */
    public function formatApiPost($post)
    {
        $data = [];

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
        if (!$this->create_at) $this->create_at = $this->BDb->now();
        $this->update_at = $this->BDb->now();
        return true;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        $customer = $this->Sellvana_Customer_Model_Customer->load($this->customer_id);
        if (!$customer->default_billing_id) {
            $customer->default_billing_id = $this->id();
        }
        if (!$customer->default_shipping_id) {
            $customer->default_shipping_id = $this->id();
        }
        if ($customer->is_dirty()) {
            $customer->save();
        }
    }

    /**
     * @param $address
     * @param Sellvana_Customer_Model_Customer $customer
     */
    public function newShipping($address, $customer)
    {
        $data = ['address' => $address];
        $this->import($data, $customer, 'shipping');
    }

    /**
     * @param $address
     * @param Sellvana_Customer_Model_Customer $customer
     */
    public function newBilling($address, $customer)
    {
        $data = ['address' => $address];
        $this->import($data, $customer, 'billing');
    }

    /**
     * @param $data
     * @param Sellvana_Customer_Model_Customer $cust
     * @param string $atype
     * @return $this
     * @throws BException
     */
    public function import($data, $cust, $atype = 'billing')
    {
        $addr = $this->create(['customer_id' => $cust->id]);

        if (!empty($data['address'])) {
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

    public function getFields()
    {
        return static::$fields;
    }
}
