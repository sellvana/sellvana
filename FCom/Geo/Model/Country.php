<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Geo_Model_Country
 *
 * @property string $iso
 * @property string $iso3
 * @property int $numcode
 * @property string $name
 */
class FCom_Geo_Model_Country extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_geo_country';
    protected static $_origClass = __CLASS__;
    protected static $_id_column = 'iso';

    protected static $_optionsCache = [];

    public function options($limit = null)
    {
        $key = $limit ? $limit : '-';
        if (empty(static::$_optionsCache[$key])) {
            $orm = $this->orm('c')->order_by_asc('name');
            if ($limit) {
                $orm->where_in('iso', explode(',', $limit));
            }
            static::$_optionsCache[$key] = $orm->find_many_assoc('iso', 'name');
        }
        return static::$_optionsCache[$key];
    }

    public function getIsoByName($name)
    {
        static $countries;
        if (!$countries) {
            $countries = array_flip($this->options());
        }
        return !empty($countries[$name]) ? $countries[$name] : null;
    }
}
