<?php

class FCom_CatalogIndex_Model_Field extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_index_field';

    protected static $_indexedFields;

    static public function getFields($context='all', $where=null)
    {
        if (!static::$_indexedFields) {
            $orm = static::orm();
            if ($where) {
                $orm->where($where);
            }
            $fields = $orm->order_by_asc('filter_order')->find_many();
            foreach ($fields as $f) {
                $k = $f->field_name;
                static::$_indexedFields['all'][$k] = $f;
                if ($f->sort_type!=='none') {
                    static::$_indexedFields['sort'][$k] = $f;
                }
                if ($f->filter_type!=='none') {
                    static::$_indexedFields['filter'][$k] = $f;
                }
                if ($f->search_type!=='none') {
                    static::$_indexedFields['search'][$k] = $f;
                }
            }
        }
        return static::$_indexedFields[$context];
    }

    static public function indexCategory($products)
    {
        $data = array();
        foreach ($products as $pId=>$p) {
            $data[$pId] = 'Category 1';
        }
        return $data;
    }

    static public function indexPriceRange($products)
    {
        $data = array();
        foreach ($products as $pId=>$p) {
            $data[$pId] = '$10 - $20';
        }
        return $data;
    }
}