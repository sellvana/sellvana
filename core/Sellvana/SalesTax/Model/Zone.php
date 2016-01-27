<?php

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

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) {
            return false;
        }

        if ($this->get('zone_type') === 'postcode') {
            if ($this->get('postcode')) {
                $this->set('postcode_from', $this->get('postcode'));
            }
            $this->set('postcode_to', $this->get('postcode_from'));
        }

        return true;
    }

    public function getAllZones()
    {
        //select id, (case when title then title when zone_type='region' THEN CONCAT_WS('/', region, country) WHEN zone_type='country' THEN country WHEN zone_type='postcode' THEN postcode_from WHEN zone_type='postrange' THEN CONCAT_WS('..', postcode_from, postcode_to) END) as title from fcom_salestax_zone;
        return $this->orm()
            ->select('id')
            ->select_expr("(CASE
                    WHEN title IS NOT NULL THEN title
                    WHEN zone_type='region' THEN CONCAT_WS('/', region, country)
                    WHEN zone_type='country' THEN country
                    WHEN zone_type='postcode' THEN postcode_from
                    WHEN zone_type='postrange' THEN CONCAT_WS('..', postcode_from, postcode_to)
                    END)", 'title')->find_many_assoc('id', 'title');
    }
}
