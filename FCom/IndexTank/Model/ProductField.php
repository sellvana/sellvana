<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_IndexTank_Model_ProductField
 *
 * @property int $id
 * @property string $field_name
 * @property string $field_nice_name
 * @property string $field_type
 * @property int $search
 * @property int $facets
 * @property int $scoring
 * @property int $var_number
 * @property int $priority
 * @property string $filter enum('','inclusive','exclusive')
 * @property string $source_type
 * @property string $source_value
 * @property int $sort_order
 *
 * DI
 * @property FCom_IndexTank_Model_ProductField $FCom_IndexTank_Model_ProductField
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

    /**
     * @return array
     */
    public function getList()
    {
        /** @var FCom_IndexTank_Model_ProductField $productFields */
        $productFields = $this->FCom_IndexTank_Model_ProductField->orm()->find_many();
        $result = [];
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
    public function getSearchList()
    {
        /** @var FCom_IndexTank_Model_ProductField $productFields */
        $productFields = $this->FCom_IndexTank_Model_ProductField->orm()
                ->where('search', 1)->find_many();
        $result = [];
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
    public function getInclusiveList()
    {
        /** @var FCom_IndexTank_Model_ProductField $productFields */
        $productFields = $this->FCom_IndexTank_Model_ProductField->orm()
                ->where('filter', 'inclusive')->find_many();
        $result = [];
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

}
