<?php

class FCom_IndexTank_Model_ProductField extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_product_field';

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Model_ProductField
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    public function get_list()
    {
        $product_fields = FCom_IndexTank_Model_ProductField::i()->orm()->find_many();
        $result = array();
        foreach($product_fields as $p){
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    public function get_facets_list()
    {
        $product_fields = FCom_IndexTank_Model_ProductField::i()->orm()
                ->where('facets', 1)->find_many();
        $result = array();
        foreach($product_fields as $p){
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    public function get_search_list()
    {
        $product_fields = FCom_IndexTank_Model_ProductField::i()->orm()
                ->where('search', 1)->find_many();
        $result = array();
        foreach($product_fields as $p){
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    public function get_varialbes_list()
    {
        $product_fields = FCom_IndexTank_Model_ProductField::i()->orm()
                ->where('scoring', 1)->find_many();
        $result = array();
        foreach($product_fields as $p){
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    public function get_inclusive_list()
    {
        $product_fields = FCom_IndexTank_Model_ProductField::i()->orm()
                ->where('filter', 'inclusive')->find_many();
        $result = array();
        foreach($product_fields as $p){
            $result[$p->field_name] = $p;
        }
        return $result;
    }

}
