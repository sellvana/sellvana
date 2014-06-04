<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    public function getList()
    {
        $productFields = $this->FCom_IndexTank_Model_ProductField->orm()->find_many();
        $result = [];
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    public function getFacetsList()
    {
        $productFields = $this->FCom_IndexTank_Model_ProductField->orm()
                ->where('facets', 1)
                ->order_by_asc('sort_order')
                ->find_many();
        $result = [];
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    public function getSearchList()
    {
        $productFields = $this->FCom_IndexTank_Model_ProductField->orm()
                ->where('search', 1)->find_many();
        $result = [];
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    public function getVariablesList()
    {
        $productFields = $this->FCom_IndexTank_Model_ProductField->orm()
                ->where('scoring', 1)->find_many();
        $result = [];
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    public function getInclusiveList()
    {
        $productFields = $this->FCom_IndexTank_Model_ProductField->orm()
                ->where('filter', 'inclusive')->find_many();
        $result = [];
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

}
