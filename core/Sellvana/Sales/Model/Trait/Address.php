<?php

/**
 * Class Sellvana_Sales_Model_Trait_Address
 *
 * @property BLayout $BLayout
 * @property BLocale $BLocale
 * @property BValidate $BValidate
 */
trait Sellvana_Sales_Model_Trait_Address
{
    public function addressValidationRules($type, $country = null)
    {
        $rules = [
            [$type . '_firstname', '@required'],
            [$type . '_lastname', '@required'],
            [$type . '_street1', '@required'],
            [$type . '_city', '@required'],
            [$type . '_country', '@required'],
        ];
        if (null === $country) {
            $country = $this->get($type . '_country');
        }
        if ($this->BLocale->postcodeRequired($country)) {
            $rules[] = [$type . '_postcode', '@required'];
        }
        if ($this->BLocale->regionRequired($country)) {
            $rules[] = [$type . '_region', '@required'];
        }
        return array_merge(static::$_validationRules, $rules);
    }

    public function fullName($atype)
    {
        $name = $this->get($atype . '_firstname');
        $name .= ' ' . $this->get($atype . '_lastname');
        return $name;
    }

    public function addressAsHtml($atype)
    {
        return $this->BLayout->getView('sales/address-card')->set(['model' => $this, 'atype' => $atype])->render();
    }

    public function addressAsArray($atype)
    {
        $country = $this->get($atype . '_country');
        $arr = [
            'atype'     => $atype,
            'email'     => $this->get('customer_email'),
            'company'   => $this->get($atype . '_company'),
            'attn'      => $this->get($atype . '_attn'),
            'firstname' => $this->get($atype . '_firstname'),
            'lastname'  => $this->get($atype . '_lastname'),
            'street1'   => $this->get($atype . '_street1'),
            'street2'   => $this->get($atype . '_street2'),
            //'street3'   => $this->get($atype . '_street3'),
            'city'      => $this->get($atype . '_city'),
            'region'    => $this->get($atype . '_region'),
            'postcode'  => $this->get($atype . '_postcode'),
            'country'   => !empty($countries[$country]) ? $countries[$country] : $country,
            'phone'     => $this->get($atype . '_phone'),
            'fax'       => $this->get($atype . '_fax'),
        ];
        return $arr;
    }

    public function addressAsObject($atype)
    {
        $a = $this->addressAsArray($atype);
        //return new stdClass($a);
        return new BData($a);
    }

    public function importAddressFromArray($a, $atype = null)
    {
        return $this->importAddressFromObject($a, new BData($a));
    }

    public function importAddressFromObject($a, $atype = null)
    {
        if (!$a instanceof BData && !$a instanceof BModel) {
            throw new BException('Invalid address parameter type');
        }
        if (null === $atype && $a->atype) {
            $atype = $a->atype;
        }
        $this->set([
            $atype . '_company' => $a->company,
            $atype . '_attn' => $a->attn,
            $atype . '_firstname' => $a->firstname,
            $atype . '_lastname' => $a->lastname,
            $atype . '_street1' => $a->street1,
            $atype . '_street2' => $a->street2,
            $atype . '_city' => $a->city,
            $atype . '_region' => $a->region,
            $atype . '_postcode' => $a->postcode,
            $atype . '_country' => $a->country,
            $atype . '_phone' => $a->phone,
            $atype . '_fax' => $a->fax,
        ]);
        return $this;
    }

    public function validateAddress($data, $moreRules = [], $formName = null)
    {
        $rules = [
            ['firstname', '@required'],
            #['firstname', '@alphanum'],
            ['lastname', '@required'],
            #['lastname', '@alphanum'],
            ['email', '@required'],
            ['email', '@email'],
            ["street1", '@required'],
            ["city", '@required'],
            ["country", '@required'],
            ["region", '@required'],
            ["postcode", '@required'],
        ];
        $rules = array_merge($rules, $moreRules);

        return $this->BValidate->validateInput($data, $rules, $formName, $this);
    }
}
