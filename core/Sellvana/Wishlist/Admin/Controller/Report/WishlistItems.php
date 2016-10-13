<?php

/**
 * Class Sellvana_Wishlist_Admin_Controller_Report_WishlistItems
 */
class Sellvana_Wishlist_Admin_Controller_Report_WishlistItems extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Wishlist_Model_WishlistItem';
    protected $_mainTableAlias = 'wi';
    protected $_permission = 'catalog/reports';
    protected $_navPath = 'reports/catalog/wishlist_items';
    protected $_gridHref = 'catalog/report/wishlist_items';
    protected $_gridTitle = 'Products in Wishlists';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'create_at', 'index' => 'wi.create_at', 'cell' => 'datetime'],
            ['name' => 'days_in_wishlist', 'index' => 'days_in_wishlist'],
            ['name' => 'customer_name', 'index' => 'customer_name'],
            ['name' => 'customer_group', 'index' => 'cg.title'],
//            ['name' => 'status', 'index' => 'last_sold_date'],
            ['name' => 'wishlist_id', 'index' => 'w.id'],
            ['name' => 'product_name', 'index' => 'p.product_name'],
            ['name' => 'product_sku', 'index' => 'p.product_sku'],
            ['name' => 'comment', 'index' => 'pi.comment'],
//            ['name' => 'qty_in_wishlist', 'index' => 'qty_in_wishlist'],
            ['name' => 'qty_in_stock', 'index' => 'i.qty_in_stock'],
        ];

        $config['filters'] = [
        ];

        return $config;
    }

    /**
     * @return array
     */
    protected function _getFieldLabels()
    {
        return [
            'create_at' => 'Date Added',
            'days_in_wishlist' => 'Days in Wishlist',
            'customer_name' => 'Customer Name',
            'customer_group' => 'Customer Group',
            'status' => 'Wishlist Status',
            'wishlist_id' => 'Wishlist ID',
            'product_name' => 'Product Name',
            'product_sku' => 'Product SKU',
            'comment' => 'Comment',
            'qty_in_wishlist' => 'Qty in Wishlist',
            'qty_in_stock' => 'Qty in Stock',
        ];
    }


    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->join('Sellvana_Wishlist_Model_Wishlist', 'wi.wishlist_id = w.id', 'w')
            ->join('Sellvana_Catalog_Model_Product', 'wi.product_id = p.id', 'p')
            ->left_outer_join('Sellvana_Customer_Model_Customer', 'w.customer_id = c.id', 'c')
            ->left_outer_join('Sellvana_Catalog_Model_InventorySku', 'p.inventory_sku = i.inventory_sku', 'i')
            ->left_outer_join('Sellvana_CustomerGroups_Model_Group', 'cg.id = c.customer_group', 'cg')
            ->select_expr('DATEDIFF(NOW(), wi.create_at)', 'days_in_wishlist')
            ->select_expr("CONCAT(c.firstname, ' ', c.lastname)", 'customer_name')
            ->select(['p.product_sku', 'p.product_name', 'i.qty_in_stock', 'customer_group' => 'cg.title'])
            ->group_by('wi.id')
        ;
    }
}