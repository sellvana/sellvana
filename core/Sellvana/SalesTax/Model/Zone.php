<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_SalesTax_Model_Zone extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_salestax_zone';
    protected static $_origClass = __CLASS__;

    protected static $_fieldOptions = [
        'zone_type' => [
            'country'   => 'Country',
            'region'    => 'Region',
            'postrange' => 'Postcode Range',
            'postcode'  => 'Postcode',
        ],
    ];

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['zone_type', 'title', 'postcode_from', 'postcode_to'],
    ];

    public function getAllZones()
    {
        return $this->orm()->find_many_assoc('id', 'title');
    }
}
