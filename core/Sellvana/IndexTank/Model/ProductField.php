<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_IndexTank_Model_ProductField
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
 * @property Sellvana_IndexTank_Model_ProductField $Sellvana_IndexTank_Model_ProductField
 */
class Sellvana_IndexTank_Model_ProductField extends FCom_Core_Model_Abstract
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
     * @return array
     */
    public function getList()
    {
        /** @var Sellvana_IndexTank_Model_ProductField $productFields */
        $productFields = $this->Sellvana_IndexTank_Model_ProductField->orm()->find_many();
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
        $productFields = $this->Sellvana_IndexTank_Model_ProductField->orm()
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
        /** @var Sellvana_IndexTank_Model_ProductField $productFields */
        $productFields = $this->Sellvana_IndexTank_Model_ProductField->orm()
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
        $productFields = $this->Sellvana_IndexTank_Model_ProductField->orm()
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
        /** @var Sellvana_IndexTank_Model_ProductField $productFields */
        $productFields = $this->Sellvana_IndexTank_Model_ProductField->orm()
                ->where('filter', 'inclusive')->find_many();
        $result = [];
        foreach ($productFields as $p) {
            $result[$p->field_name] = $p;
        }
        return $result;
    }

}
