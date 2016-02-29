<?php

/**
 * Copyright 2015 Sellvana Inc
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package Sellvana
 * @link https://www.sellvana.com/
 * @author Vadims Bucinskis <vadim.buchinsky@gmail.com>
 * @copyright (c) 2010-2014 Boris Gurvich
 * @license http://www.apache.org/licenses/LICENSE-2.0.html
 * @since 0.5.2.0
 */

/**
 * Class Sellvana_Sales_Admin_Dashboard
 *
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Model_StateCustom $Sellvana_Sales_Model_StateCustom
 * @property Sellvana_Sales_Model_Order_State_Overall $Sellvana_Sales_Model_Order_State_Overall
 * @property Sellvana_Sales_Model_Order_Item $Sellvana_Sales_Model_Order_Item
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */
class Sellvana_Sales_Admin_Dashboard extends FCom_Admin_Dashboard_Abstract
{
    static protected $_origClass      = __CLASS__;
    protected        $_modelClass     = 'Sellvana_Sales_Model_Order';

    /**
     * @param ORM $orm
     * @param string $field
     */
    protected function _processFilters($orm, $field = 'o.create_at')
    {
        $filter = $this->BApp->get('dashboard_date_filter');
        $cond = $field . ' ' . $filter['condition'];

        if ($filter) {
            $orm->where_raw($cond, $filter['params']);
        }
    }

    /**
     * @return array
     */
    public function getOrderRecent()
    {
        $dayLimit = $this->BConfig->get('modules/Sellvana_Sales/recent_day');

        $orm = $this->{$this->_modelClass}->orm('o')
            ->join($this->Sellvana_Customer_Model_Customer->table(), ['o.customer_id', '=', 'c.id'], 'c')
            ->select(['o.*', 'c.firstname', 'c.lastname'])
            ->order_by_desc('o.create_at');
        if ($dayLimit) {
            $orm->where_raw("DATE_ADD(o.create_at, INTERVAL {$dayLimit} DAY) > NOW()");
        }

        return $orm->find_many();
    }

    /**
     * @return array
     * @TODO: need to check result
     */
    public function getOrderTotal()
    {
        $orm = $this->Sellvana_Sales_Model_StateCustom->orm('s')
            ->left_outer_join($this->{$this->_modelClass}->table(), ['o.state_custom', '=', 's.state_code'], 'o')
            ->group_by('s.id')
            ->select_expr('COUNT(o.id)', 'order')
            ->where('s.entity_type', 'order')
            ->select(['s.id', 's.state_label']);

        $this->_processFilters($orm);

        return $orm->find_many();
    }

    /**
     * @return string
     */
    public function getAvgOrderTotal()
    {
        $orm = $this->{$this->_modelClass}->orm('o')
            ->select_expr('AVG(o.grand_total)', 'avg_total');

        $this->_processFilters($orm);

        $result = (float)$orm->find_one()->get('avg_total');
        return $result;
    }

    /**
     * @return $this|ORM
     */
    protected function _getLateOrdersPrepare()
    {
        $state = $this->Sellvana_Sales_Model_Order_State_Overall->origClass();

        $ignoreStateList = [
//            $state::PENDING,
//            $state::PLACED,
//            $state::REVIEW,
//            $state::FRAUD,
//            $state::LEGIT,
//            $state::PROCESSING,
//            $state::BACKORDERED,
            $state::COMPLETE,
//            $state::CANCEL_REQUESTED,
            $state::CANCELED,
            $state::ARCHIVED
        ];

        $dayLimit = $this->BConfig->get('modules/Sellvana_Sales/orders_late_day');

        $orders = $this->{$this->_modelClass}->orm('o')
            ->select([
                'o.unique_id',
                'o.create_at',
                'o.update_at'
            ])
            ->where_not_in('o.state_overall', $ignoreStateList);

        if ($dayLimit) {
            $orders->where_raw("DATE_ADD(o.update_at, INTERVAL {$dayLimit} DAY) < NOW()");
        }

        return $orders;
    }

    /**
     * @return array
     */
    public function getLateOrders()
    {
        $limitConfigPath = 'modules/Sellvana_Sales/orders_late_limit';

        $orders = $this->_getLateOrdersPrepare();
        $orders->limit($this->getLimit($limitConfigPath));

        return $orders->find_many();
    }

    /**
     * @return int
     */
    public function getLateOrdersCount()
    {
        $orders = $this->_getLateOrdersPrepare();

        return $orders->count();
    }

    /**
     * @return array
     */
    public function getTopProducts()
    {
        //TODO: change the path of this settings to the accepted standard for widgets
        $limit = $this->BConfig->get('modules/Sellvana_Sales/top_products');

        $items = $this->{$this->_modelClass}->orm('o')
            ->join($this->Sellvana_Sales_Model_Order_Item->table(), 'oi.order_id = o.id', 'oi')
            ->join($this->Sellvana_Catalog_Model_Product->table(), 'p.id = oi.product_id', 'p')
            ->select_expr(
                'SUM(IF(oi.cost IS NULL, oi.row_total ,(oi.row_total - ROUND(oi.qty_ordered * oi.cost, 2))))',
                'profit_fixed'
            );
        $this->_processFilters($items);
        $totals = clone $items;
        $total_profit = $totals->find_one()->get('profit_fixed') ?: 0;
        $profitExpr = "ROUND(SUM(IF(oi.cost IS NULL, oi.row_total,(oi.row_total - ROUND(oi.qty_ordered * oi.cost, 2)))) / {$total_profit} * 100, 2)";
        $items->select('p.*')
            ->select_expr('SUM(oi.row_total)', 'revenue')
            ->select_expr('SUM(oi.qty_ordered)', 'qty')
            ->select_expr($profitExpr, 'profit_pc')
            ->group_by('oi.product_id')
            ->order_by_expr($profitExpr . ' DESC');
        if ($limit) {
            $items->limit($limit);
        }

        $result = $items->find_many();
        return $result;
    }
}