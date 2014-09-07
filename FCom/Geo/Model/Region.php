<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Geo_Model_Region extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_geo_region';
    protected static $_origClass = __CLASS__;

    protected static $_optionsCache = [];
    protected static $_allOptionsLoaded;
    protected static $_importExportProfile = [
      'unique_key' => ['country', 'code',],
    ];
    public function options($country)
    {
        if (empty(static::$_optionsCache[$country])) {
            static::$_optionsCache[$country] = $this->orm('s')
                ->where('country', $country)->find_many_assoc('code', 'name');
        }
        return static::$_optionsCache[$country];
    }

    public function allOptions()
    {
        if (!static::$_allOptionsLoaded) {
            $regions = $this->orm('s')->find_many();
            foreach ($regions as $r) {
                static::$_optionsCache[$r->country][$r->code] = $r->name;
            }
        }
        return static::$_optionsCache;
    }

    public function findByName($country, $name, $field = null)
    {
        $result = $this->orm('s')->where('country', $country)->where('name', $name)->find_one();
        if (!$result) return null;
        return $field ? $result->$field : $result;
    }
}
