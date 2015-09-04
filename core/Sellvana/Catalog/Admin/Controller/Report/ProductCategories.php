<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Admin_Controller_Report_ProductCategories
 */
class Sellvana_Catalog_Admin_Controller_Report_ProductCategories extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Catalog_Model_Product';
    protected $_mainTableAlias = 'p';
    protected $_permission = 'catalog/reports';
    protected $_navPath = 'reports/integrity/product_categories';
    protected $_gridHref = 'catalog/report/integrity/product_categories';
    protected $_gridTitle = 'Products Without Categories';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'product_sku', 'index' => 'p.product_sku'],
            ['name' => 'product_name', 'index' => 'p.product_name'],
        ];

        $config = $this->_addProductCustomFields($config);

        return $config;
    }

    /**
     * @return array
     */
    protected function _getFieldLabels()
    {
        $labels = [
            'product_sku' => 'SKU',
            'product_name' => 'Name',
        ];

        $labels = array_merge($labels, $this->_getProductCustomFieldLabels());

        return $labels;
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);
        $orm->left_outer_join('Sellvana_Catalog_Model_CategoryProduct', 'p.id = cp.product_id', 'cp')
            ->where_null('cp.category_id')
            ->group_by('p.id');
    }
}