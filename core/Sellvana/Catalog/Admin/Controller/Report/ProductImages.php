<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Admin_Controller_Report_ProductsWithoutImages
 *
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductMedia $Sellvana_Catalog_Model_ProductMedia
 */
class Sellvana_Catalog_Admin_Controller_Report_ProductImages extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Catalog_Model_Product';
    protected $_mainTableAlias = 'p';
    protected $_permission = 'catalog/reports';
    protected $_navPath = 'reports/integrity/product_images';
    protected $_gridHref = 'integrity/report/product_images';
    protected $_gridTitle = 'Products Without Images';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'product_sku', 'index' => 'p.product_sku'],
            ['name' => 'product_name', 'index' => 'p.product_name'],

            ['name' => 'image_count', 'index' => 'm.image_count', 'hidden' => true],
        ];

        $config['filters'] = [
            ['field' => 'image_count', 'type' => 'number-range', 'callback' => 'filterByImageCount', 'op' => 'equal', 'val' => '0'],
        ];

        return $config;
    }

    /**
     * @return array
     */
    protected function _getFieldLabels()
    {
        return [
            'product_sku' => 'SKU',
            'product_name' => 'Name',
            'image_count' => 'Image Count',
        ];
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();
        $tMedia = $this->Sellvana_Catalog_Model_ProductMedia->table();

        $orm->raw_join("INNER JOIN (
            SELECT `sub_p`.`id`, IFNULL(COUNT(sub_m.id), 0) as `image_count`
            FROM {$tProduct} sub_p
            LEFT JOIN {$tMedia} sub_m ON (sub_m.product_id = sub_p.id)
            GROUP BY `sub_p`.`id`
        )", 'm.id = p.id', 'm')
            ->group_by('p.id');

        $pers = $this->FCom_Admin_Model_User->personalize();
        $filtersChanged = !empty($pers['grid'][self::$_origClass]['state']['filters']['image_count']);
        if (!$this->BUtil->BRequest->get('filters') && !$filtersChanged) {
            $this->filterByImageCount([], 0, $orm);
        }
    }

    /**
     * @param array $filter
     * @param string $val
     * @param BORM $orm
     *
     * @return bool
     */
    public function filterByImageCount($filter, $val, $orm)
    {
        if (!$val) {
            $orm->where_equal('m.image_count', 0);
            return true;
        }

        return false;
    }
}