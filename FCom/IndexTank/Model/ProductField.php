<?php

/**
 * Class FCom_IndexTank_Model_ProductField
 *
 * @property string field_name
 * @property string field_nice_name
 * @property string field_type
 * @property int facets
 * @property int search
 * @property string source_type
 * @property string source_value
 */
class FCom_IndexTank_Model_ProductField extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_product_field';

    protected static $_fieldOptions = [
        'search' => [
            '1' => 'Yes',
            '0' => 'No'
        ],
        'facets' => [
            '1' => 'Yes',
            '0' => 'No'
        ],
        'scoring' => [
            '1' => 'Yes',
            '0' => 'No'
        ],
    ];

    /**
     * Shortcut to help with IDE autocompletion
     *
     * @param bool  $new
     * @param array $args
     * @return FCom_IndexTank_Model_ProductField
     */
    public static function i( $new = false, array $args = [] )
    {
        return BClassRegistry::instance( __CLASS__, $args, !$new );
    }

    public function getList()
    {
        $productFields = FCom_IndexTank_Model_ProductField::i()->orm()->find_many();
        $result = [];
        foreach ( $productFields as $p ) {
            $result[ $p->field_name ] = $p;
        }
        return $result;
    }

    public function getFacetsList()
    {
        $productFields = FCom_IndexTank_Model_ProductField::i()->orm()
                ->where( 'facets', 1 )
                ->order_by_asc( 'sort_order' )
                ->find_many();
        $result = [];
        foreach ( $productFields as $p ) {
            $result[ $p->field_name ] = $p;
        }
        return $result;
    }

    public function getSearchList()
    {
        $productFields = FCom_IndexTank_Model_ProductField::i()->orm()
                ->where( 'search', 1 )->find_many();
        $result = [];
        foreach ( $productFields as $p ) {
            $result[ $p->field_name ] = $p;
        }
        return $result;
    }

    public function getVariablesList()
    {
        $productFields = FCom_IndexTank_Model_ProductField::i()->orm()
                ->where( 'scoring', 1 )->find_many();
        $result = [];
        foreach ( $productFields as $p ) {
            $result[ $p->field_name ] = $p;
        }
        return $result;
    }

    public function getInclusiveList()
    {
        $productFields = FCom_IndexTank_Model_ProductField::i()->orm()
                ->where( 'filter', 'inclusive' )->find_many();
        $result = [];
        foreach ( $productFields as $p ) {
            $result[ $p->field_name ] = $p;
        }
        return $result;
    }

}
