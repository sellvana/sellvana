<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Geo_Model_Country extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_geo_country';
    protected static $_origClass = __CLASS__;
    protected static $_id_column = 'iso';

    protected static $_optionsCache = [];
    protected static $_importExportProfile = [
        'unique_key'                          => ['iso',],
        FCom_Core_ImportExport::AUTO_MODEL_ID => false,
    ];
    public static function options($limit = null)
    {
        $key = $limit ? $limit : '-';
        if (empty(static::$_optionsCache[$key])) {
            $orm = static::orm('c')->order_by_asc('name');
            if ($limit) {
                $orm->where_in('iso', explode(',', $limit));
            }
            static::$_optionsCache[$key] = $orm->find_many_assoc('iso', 'name');
        }
        return static::$_optionsCache[$key];
    }

    public static function getIsoByName($name)
    {
        static $countries;
        if (!$countries) {
            $countries = array_flip(static::options());
        }
        return !empty($countries[$name]) ? $countries[$name] : null;
    }
}