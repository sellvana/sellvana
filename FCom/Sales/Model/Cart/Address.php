<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * model class for table "fcom_sales_cart_address".
 * The followings are the available columns in table 'fcom_sales_cart_address':
 * @property string $id
 * @property string $cart_id
 * @property string $atype
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
 * @property string $email
 * @property string $create_at
 * @property string $update_at
 * @property string $lat
 * @property string $lng
 */
class FCom_Sales_Model_Cart_Address extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_cart_address';
    protected static $_origClass = __CLASS__;
    protected static $_validationRules = [
        ['firstname', '@required'],
        #array('firstname', '@alphanum'),
        ['lastname', '@required'],
        #array('lastname', '@alphanum'),
        ['email', '@required'],
        ['email', '@email'],
        ["street1", '@required'],
        ["city", '@required'],
        ["country", '@required'],
        ["region", '@required'],
        ["postcode", '@required'],
    ];

    public function as_html($obj = null)
    {
        if (is_null($obj)) {
            $obj = $this;
        }
        $countries = $this->BLocale->getAvailableCountries();
        return '<div class="adr">'
            . '<div class="street-address">' . $obj->street1 . '</div>'
            . ($obj->street2 ? '<div class="extended-address">' . $obj->street2 . '</div>' : '')
            . ($obj->street3 ? '<div class="extended-address">' . $obj->street3 . '</div>' : '')
            . '<span class="locality">' . $obj->city . '</span>, '
            . '<span class="region">' . $obj->region . '</span> '
            . '<span class="postal-code">' . $obj->postcode . '</span>'
            . '<div class="country-name">' . (!empty($countries[$obj->country]) ? $countries[$obj->country] : $obj->country) . '</div>'
            . '</div>';

    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;
        if (!$this->create_at) $this->create_at = $this->BDb->now();
        $this->update_at = $this->BDb->now();
        return true;
    }

    public function exportToCustomer($customer)
    {
        $data = $this->as_array();
        $data['customer_id'] = $customer->id();
        $custAddress = $this->FCom_Customer_Model_Address->create($data)->save();
        return $custAddress;
    }

    public function findByCartType($cartId, $atype = 'shipping')
    {
        return $this->orm()->where("cart_id", $cartId)->where('atype', $atype)->find_one();
    }

    public function newAddress($cartId, $newAddress)
    {
        if (is_object($newAddress)) {
            $data = $newAddress->as_array();
        } else {
            $data = $newAddress;
        }
        if (isset($data['id'])) {
            unset($data['id']);
        }
        if (empty($data['atype'])) {
            $data['atype'] = 'shipping';
        }

        $address = $data;
        $address['cart_id'] = $cartId;

        $newAddress = $this->findByCartType($cartId, $address['atype']);
        if (!$newAddress) {
            $newAddress = $this->create($address);
        } else {
            $newAddress->set($address);
        }
        $newAddress->save();
        return $newAddress;
    }
}
