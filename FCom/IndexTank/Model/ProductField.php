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
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
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

    public function getCustomFieldsSorted()
    {
        $productFields = FCom_IndexTank_Model_ProductField::i()->orm()
                ->where('facets', 1)
                ->where('source_type', 'custom_field')
                ->find_many();

        $fieldNames = array();
        foreach ($productFields as $p) {
            $fieldNames[] = $p->source_value;
        }

        $fields = FCom_CustomField_Model_Field::orm('f')
                ->join(FCom_CustomField_Model_SetField::table(), "sf.field_id = f.id", "sf")
                ->where_in("f.field_code", $fieldNames)
                ->order_by_asc('sf.position')
                ->find_many();

        $result = array();
        foreach($fields as $f) {
            $result[$f->field_code] = $f->position;
        }
        return $result;
    }

}
