<?php

class FCom_Geo_Model_Region extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_geo_region';
    protected static $_origClass = __CLASS__;

    protected static $_optionsCache = array();
    protected static $_allOptionsLoaded;
    protected static $_importExportProfile = array (
      'unique_key' => array ( 'country', 'code', ),
    );
    public static function options( $country )
    {
        if ( empty( static::$_optionsCache[ $country ] ) ) {
            static::$_optionsCache[ $country ] = static::orm( 's' )
                ->where( 'country', $country )->find_many_assoc( 'code', 'name' );
        }
        return static::$_optionsCache[ $country ];
    }

    public static function allOptions()
    {
        if ( !static::$_allOptionsLoaded ) {
            $regions = static::orm( 's' )->find_many();
            foreach ( $regions as $r ) {
                static::$_optionsCache[ $r->country ][ $r->code ] = $r->name;
            }
        }
        return static::$_optionsCache;
    }

    public static function findByName( $country, $name, $field = null )
    {
        $result = static::orm( 's' )->where( 'country', $country )->where( 'name', $name )->find_one();
        if ( !$result ) return null;
        return $field ? $result->$field : $result;
    }
}