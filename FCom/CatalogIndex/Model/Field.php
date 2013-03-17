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

    static public function indexCategory($products, $field)
    {
        // TODO: prefetch categories
        $data = array();
        foreach ($products as $p) {
            $categories = $p->categories();
            foreach ((array)$p->categories() as $c) {
                $data[$p->id][$c->url_path] = $c->url_path.' ==> '.$c->node_name;
                foreach ((array)$c->category()->ascendants() as $c1) { //TODO: configuration?
                    $data[$p->id][$c1->url_path] = $c1->url_path.' ==> '.$c1->node_name;
                }
            }
        }
        return $data;
    }

    static public function indexPriceRange($products, $field)
    {
        $data = array();
        foreach ($products as $p) {
            $f = $field->source_callback ? $field->source_callback : $field->field_name;
            $m = isset($p->$f) ? $p->$f : $p->base_price;
            if     ($m <   100) $v = '0-99      ==> $0 to $99';
            elseif ($m <   200) $v = '100-199   ==> $100 to $199';
            elseif ($m <   300) $v = '200-299   ==> $200 to $299';
            elseif ($m <   400) $v = '300-399   ==> $300 to $399';
            elseif ($m <   500) $v = '400-499   ==> $400 to $499';
            elseif ($m <   600) $v = '500-599   ==> $500 to $599';
            elseif ($m <   700) $v = '600-699   ==> $600 to $699';
            elseif ($m <   800) $v = '700-799   ==> $700 to $799';
            elseif ($m <   900) $v = '800-899   ==> $800 to $899';
            elseif ($m <  1000) $v = '900-999   ==> $900 to $999';
            elseif ($m <  2000) $v = '1000-1999 ==> $1000 to $1999';
            elseif ($m <  3000) $v = '2000-2999 ==> $2000 to $2999';
            elseif ($m <  4000) $v = '3000-3999 ==> $3000 to $3999';
            elseif ($m <  5000) $v = '4000-4999 ==> $4000 to $4999';
            elseif ($m <  6000) $v = '5000-5999 ==> $5000 to $5999';
            elseif ($m <  7000) $v = '6000-6999 ==> $6000 to $6999';
            elseif ($m <  8000) $v = '7000-7999 ==> $7000 to $7999';
            elseif ($m <  9000) $v = '8000-8999 ==> $8000 to $8999';
            elseif ($m < 10000) $v = '9000-9999 ==> $9000 to $9999';
            else                $v = '10000-    ==> $10000 or more';
            $data[$p->id] = $v;
        }
        return $data;
    }
}