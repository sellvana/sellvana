<?php

/**
 * Class Sellvana_Catalog_Admin_Controller_Report_LowInventory
 *
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Item $Sellvana_Sales_Model_Order_Item
 */
class Sellvana_Catalog_Admin_Controller_Report_LowInventory extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Catalog_Model_InventorySku';
    protected $_mainTableAlias = 'i';
    protected $_permission = 'catalog/reports';
    protected $_navPath = 'reports/catalog/low_inventory';
    protected $_gridHref = 'catalog/report/inventory/low_inventory';
    protected $_gridTitle = 'Low Inventory';

    protected $_statDays = [7, 30, 90, 180];

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'inventory_sku', 'index' => 'i.inventory_sku'],
            ['name' => 'title', 'index' => 'i.title'],
            ['name' => 'is_hidden', 'index' => 'p.is_hidden', 'options' => [1 => 'Inactive', 0 => 'Active']],
            ['name' => 'qty_in_stock', 'index' => 'i.qty_in_stock'],
//            ['name' => 'out_of_stock_date', 'index' => 'out_of_stock_date'],
            ['name' => 'last_sold_date', 'index' => 'last_sold_date', 'cell' => 'datetime'],
        ];

        foreach ($this->_statDays as $dayCount) {
            $fieldId = "qty_sold_{$dayCount}d";
            $config['columns'][] = ['name' => $fieldId, 'index' => $fieldId];
        }

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
            'inventory_sku' => 'SKU',
            'title' => 'Name',
            'is_hidden' => 'Active/Inactive',
            'qty_in_stock' => 'Qty in Stock',
            'out_of_stock_date' => 'Date Went Out of Stock',
            'last_sold_date' => 'Last Sold Date',
            'qty_sold_7d' => 'Qty Sold Last 7 days',
            'qty_sold_30d' => 'Qty Sold Last 30 days',
            'qty_sold_90d' => 'Qty Sold Last 90 days',
            'qty_sold_180d' => 'Qty Sold Last 180 days',
        ];
    }


    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $tOrder = $this->Sellvana_Sales_Model_Order->table();
        $tOrderItem = $this->Sellvana_Sales_Model_Order_Item->table();
        $orm->join('Sellvana_Catalog_Model_Product', 'i.inventory_sku = p.inventory_sku', 'p')
            ->raw_join("LEFT JOIN (
                SELECT `sub_oi`.`product_id`, MAX(`sub_o`.`create_at`) as `last_order`
                FROM {$tOrder} as `sub_o`
                INNER JOIN {$tOrderItem} as `sub_oi` ON (`sub_oi`.`order_id` = `sub_o`.`id`)
                GROUP BY `sub_oi`.`product_id`
            )", "last_stat.product_id = p.id", "last_stat")
            ->select("last_stat.last_order", "last_sold_date")
            ->select(['p.is_hidden'])
            ->group_by('p.id')
            ->where('i.manage_inventory', 1);

        foreach ($this->_statDays as $dayCount) {
            $orm->raw_join("LEFT JOIN (
                SELECT `sub_oi`.`product_id`, SUM(`sub_oi`.`qty_ordered`) as `qty`
                FROM {$tOrder} as `sub_o`
                INNER JOIN {$tOrderItem} as `sub_oi` ON (`sub_oi`.`order_id` = `sub_o`.`id`)
                WHERE DATE_ADD(sub_o.create_at, INTERVAL {$dayCount} DAY) > NOW()
                GROUP BY `sub_oi`.`product_id`
            )", "stat{$dayCount}d.product_id = p.id", "stat{$dayCount}d");
            $orm->select_expr("IFNULL(stat{$dayCount}d.qty, 0)", "qty_sold_{$dayCount}d");
        }

        $defaultMinQty = $this->BConfig->get('modules/Sellvana_Catalog/qty_notify_admin');
        if (!$defaultMinQty) {
            $orm->where_not_null('i.qty_notify_admin')
                ->where_raw('i.qty_in_stock <= i.qty_notify_admin');
        } else {
            $defaultMinQty--;
            $orm->where_raw("i.qty_in_stock <= IFNULL(i.qty_notify_admin, {$defaultMinQty})");
        }
    }
}