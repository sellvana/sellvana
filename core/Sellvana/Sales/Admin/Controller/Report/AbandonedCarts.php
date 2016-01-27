<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Report_CustomerGroup
 *
 */
class Sellvana_Sales_Admin_Controller_Report_AbandonedCarts extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Cart';
    protected $_recordName = 'Cart';
    protected $_mainTableAlias = 'c';
    protected $_permission = 'sales/reports';
    protected $_navPath = 'reports/sales/abandoned_carts';
    protected $_gridHref = 'sales/report/abandoned_carts';
    protected $_gridTitle = 'Abandoned Carts';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'id', 'index' => 'c.id', 'width' => 70],
            ['name' => 'customer_name', 'index' => 'customer_name'],
            ['name' => 'customer_email', 'index' => 'customer_email'],
            ['name' => 'skus', 'index' => 'skus'],
            ['name' => 'item_qty', 'index' => 'c.item_qty'],
            ['name' => 'subtotal', 'index' => 'c.subtotal'],
            ['name' => 'coupon_code', 'index' => 'c.coupon_code'],
            ['name' => 'last_page', 'index' => 'last_page'],
            ['name' => 'create_at', 'index' => 'c.create_at'],
            ['name' => 'update_at', 'index' => 'c.update_at'],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
        ];

        return $config;
    }

    /**
     * @return array
     */
    protected function _getFieldLabels()
    {
        return [
            'id' => 'Cart ID',
            'customer_name' => 'Customer Name',
            'customer_email' => 'Customer Email',
            'skus' => 'Inventory SKUs',
            'item_qty' => 'Total Qty in Cart',
            'subtotal' => 'Subtotal',
            'coupon_code' => 'Applied Coupons',
            'last_page' => 'Last Page Visited',
            'create_at' => 'Date Created',
            'update_at' => 'Last Updated Date',
        ];
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->join('Sellvana_Sales_Model_Cart_Item', 'c.id = ci.cart_id', 'ci')
            ->left_outer_join('Sellvana_Sales_Model_Order', 'c.id = o.cart_id', 'o')
            ->where_null('o.id')
            ->select_expr('IFNULL(CONCAT(c.billing_firstname, " ", c.billing_lastname), "<unknown>")', 'customer_name')
            ->select_expr('GROUP_CONCAT(ci.product_sku SEPARATOR ", ")', 'skus')
            ->group_by('c.id');
    }
}