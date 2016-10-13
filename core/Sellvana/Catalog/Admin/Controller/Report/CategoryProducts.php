<?php

/**
 * Class Sellvana_Catalog_Admin_Controller_Report_CategoryProducts
 *
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 */
class Sellvana_Catalog_Admin_Controller_Report_CategoryProducts extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Catalog_Model_Category';
    protected $_mainTableAlias = 'c';
    protected $_permission = 'catalog/reports';
    protected $_navPath = 'reports/integrity/category_products';
    protected $_gridHref = 'catalog/report/integrity/category_products';
    protected $_gridTitle = 'Categories Without Products';

    public function gridConfig()
    {
        $options = [];
        $categories = $this->Sellvana_Catalog_Model_Category->orm('cat')->find_many();
        foreach ($categories as $category) {
            if ($category->get('node_name')) {
                $options[$category->get('id')] = $category->get('node_name');
            }
        }
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'id', 'index' => 'c.id'],
            ['name' => 'node_name', 'index' => 'c.node_name'],

            ['name' => 'parent_id', 'index' => 'c.parent_id', 'options' => $options, 'hidden' => true]
        ];

        $config['filters'] = [
            ['field' => 'parent_id', 'type' => 'multiselect' ]
        ];

        return $config;
    }

    /**
     * @return array
     */
    protected function _getFieldLabels()
    {
        return [
            'id' => 'Category ID',
            'node_name' => 'Category Name',
            'parent_id' => 'Parent Category',
        ];
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);
        $orm->left_outer_join('Sellvana_Catalog_Model_CategoryProduct', 'c.id = cp.category_id', 'cp')
            ->where_null('cp.product_id')
            ->group_by('c.id');
    }
}