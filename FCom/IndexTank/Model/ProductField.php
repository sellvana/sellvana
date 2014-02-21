<?php

class FCom_IndexTank_Model_ProductField extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_product_field';

    protected static $_fieldOptions = array(
        'search' => array(
            '1' => 'Yes',
            '0' => 'No'
        ),
        'facets' => array(
            '1' => 'Yes',
            '0' => 'No'
        ),
        'scoring' => array(
            '1' => 'Yes',
            '0' => 'No'
        ),
    );

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Model_ProductField
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    public function getList()
    {
        $productFields = FCom_IndexTank_Model_ProductField::i()->orm()->find_many();
        $result = array();
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    public function getFacetsList()
    {
        $productFields = FCom_IndexTank_Model_ProductField::i()->orm()
                ->where('facets', 1)
                ->order_by_asc('sort_order')
                ->find_many();
        $result = array();
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    public function getSearchList()
    {
        $productFields = FCom_IndexTank_Model_ProductField::i()->orm()
                ->where('search', 1)->find_many();
        $result = array();
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    public function getVarialbesList()
    {
        $productFields = FCom_IndexTank_Model_ProductField::i()->orm()
                ->where('scoring', 1)->find_many();
        $result = array();
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    public function getInclusiveList()
    {
        $productFields = FCom_IndexTank_Model_ProductField::i()->orm()
                ->where('filter', 'inclusive')->find_many();
        $result = array();
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

}
