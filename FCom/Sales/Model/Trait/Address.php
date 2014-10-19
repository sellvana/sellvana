<?php defined('BUCKYBALL_ROOT_DIR') || die();

trait FCom_Sales_Model_Trait_Address
{
    public function fullName($atype)
    {
        $name = $this->get($atype . '_firstname');
        $name .= ' ' . $this->get($atype . '_lastname');
        return $name;
    }

    public function addressAsHtml($atype)
    {
        $countries = $this->BLocale->getAvailableCountries();
        $streetArr = explode("\n", $this->get($atype . '_street'));
        $country = $this->get($atype . '_country');
        $html = '<div class="adr">'
            . '<div class="street-address">' . $streetArr[0] . '</div>'
            . (!empty($streetArr[1]) ? '<div class="extended-address">' . $streetArr[1] . '</div>' : '')
            . (!empty($streetArr[2]) ? '<div class="extended-address">' . $streetArr[2] . '</div>' : '')
            . '<span class="locality">' . $this->get($atype . '_city') . '</span>, '
            . '<span class="region">' . $this->get($atype . '_region') . '</span> '
            . '<span class="postal-code">' . $this->get($atype . '_postcode') . '</span>'
            . '<div class="country-name">' . (!empty($countries[$country]) ? $countries[$country] : $country) . '</div>'
            . '</div>';
        return $html;
    }

    public function addressAsArray($atype)
    {
        $streetArr = explode("\n", $this->get($atype . '_street'));
        $country = $this->get($atype . '_country');
        $arr = [
            'atype'     => $atype,
            'company'   => $atype . '_company',
            'attn'      => $atype . '_attn',
            'firstname' => $atype . '_firstname',
            'lastname'  => $atype . '_lastname',
            'street1'   => $streetArr[0],
            'street2'   => !empty($streetArr[1]) ? $streetArr[1] : null,
            'street3'   => !empty($streetArr[2]) ? $streetArr[2] : null,
            'city'      => $atype . '_city',
            'region'    => $atype . '_region',
            'postcode'  => $atype . '_postcode',
            'country'   => !empty($countries[$country]) ? $countries[$country] : $country,
            'phone'     => $atype . '_phone',
            'fax'       => $atype . '_fax',
        ];
        return $arr;
    }

    public function addressAsObject($atype)
    {
        $a = $this->addressAsArray($atype);
        //return new stdClass($a);
        return $this->BData->i(true, [$a]);
    }

    public function importAddressFromArray($a, $atype = null)
    {
        return $this->importAddressFromObject($a, $this->BData->i(true, [$a]));
    }

    public function importAddressFromObject($a, $atype = null)
    {
        if (!$a instanceof BData || !$a instanceof BModel) {
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
            $atype . '_street' => trim($a->street1 . "\n" . $a->street2 . "\n" . $a->street3),
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
        $rules = array_merge($rules, $moreRules);

        return $this->BValidate->validateInput($data, $rules, $formName);
    }
}
