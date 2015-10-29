<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
 * Class Sellvana_Catalog_Admin_Dashboard
 *
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Catalog_Model_CategoryProduct $Sellvana_Catalog_Model_CategoryProduct
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Catalog_Model_SearchHistory $Sellvana_Catalog_Model_SearchHistory
 * @property Sellvana_Catalog_Model_SearchHistoryLog $Sellvana_Catalog_Model_SearchHistoryLog
 */
class Sellvana_Catalog_Admin_Dashboard extends FCom_Admin_Widget
{
    static protected $_origClass = __CLASS__;
    protected        $_modelClass = 'Sellvana_Catalog_Model_Product';

    /**
     * @return array
     */
    public function getLatestNewProducts()
    {
        $limitConfigPath = 'modules/Sellvana_Catalog/latest_new_limit';

        $orm = $this->{$this->_modelClass}->orm('p')
            ->join($this->Sellvana_Catalog_Model_CategoryProduct->table(), ['p.id', '=', 'cp.product_id'], 'cp')
            ->join($this->Sellvana_Catalog_Model_Category->table(), ['c.id', '=', 'cp.category_id'], 'c')
            ->select([
                'p.product_sku',
                'p.product_name',
                'p.create_at'
            ])
            ->select_expr('GROUP_CONCAT(c.node_name SEPARATOR "\n ")', 'categories')
            ->group_by('p.id')
            ->order_by_desc('p.create_at')
            ->limit($this->getLimit($limitConfigPath));

        return $orm->find_many();
    }

    /**
     * @return array
     * @TODO: need to check result
     */
    public function getLowStockProducts()
    {
        $defaultMinQty = $this->BConfig->get('modules/Sellvana_Catalog/notify_administrator_quantity');

        $orm = $this->Sellvana_Catalog_Model_InventorySku->orm('i')
            ->select(['i.inventory_sku', 'i.title', 'i.qty_in_stock'])
            ->where('i.manage_inventory', 1);

        if (!$defaultMinQty) {
            $orm->where_not_null('i.qty_notify_admin')
                ->where_raw('i.qty_in_stock <= i.qty_notify_admin');
        } else {
            $defaultMinQty--;
            $orm->where_raw("i.qty_in_stock <= IFNULL(i.qty_notify_admin, {$defaultMinQty})");
        }

        return $orm->find_many();
    }

    /**
     * @param ORM $orm
     * @param string $field
     */
    protected function _processFilters($orm, $field = 'p.create_at')
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
    public function getSearchesRecentTerms()
    {
        $limitConfigPath = 'modules/Sellvana_Catalog/searches_recent_terms_limit';

        $orm = $this->Sellvana_Catalog_Model_SearchHistory->orm('sh')
            ->select(['sh.query'])
            ->order_by_desc('sh.last_at')
            ->limit($this->getLimit($limitConfigPath));

        return $orm->find_many();
    }

    /**
     * @return array
     */
    public function getSearchesTopTerms()
    {
        $limitConfigPath = 'modules/Sellvana_Catalog/searches_top_terms_limit';

        $orm = $this->Sellvana_Catalog_Model_SearchHistoryLog->orm('shl')
            ->inner_join($this->Sellvana_Catalog_Model_SearchHistory->table(), ['shl.query_id', '=', 'sh.id'], 'sh')
            ->select(['sh.query'])
            ->select_expr('COUNT(sh.id)', 'qty')
            ->group_by('shl.query_id')
            ->order_by_desc('qty')
            ->limit($this->getLimit($limitConfigPath));

        $this->_processFilters($orm, 'shl.create_at');

        return $orm->find_many();
    }

    /**
     * @return array
     */
    public function getProductsWithoutImages()
    {
        $limitConfigPath = 'modules/Sellvana_Catalog/products_without_images_limit';

        $tProduct = $this->{$this->_modelClass}->table();
        $tMedia = $this->Sellvana_Catalog_Model_ProductMedia->table();

        $products = $this->{$this->_modelClass}->orm('p')
            ->raw_join("INNER JOIN (
                SELECT `sub_p`.`id`, IFNULL(COUNT(sub_m.id), 0) as `image_count`
                FROM {$tProduct} sub_p
                LEFT JOIN {$tMedia} sub_m ON (sub_m.product_id = sub_p.id)
                GROUP BY `sub_p`.`id`
                )", 'm.id = p.id', 'm')
            ->group_by('p.id')
            ->select([
                'p.product_sku',
                'p.product_name'
            ])
            ->where_equal('m.image_count', 0)
            ->order_by_asc('p.update_at')
            ->limit($this->getLimit($limitConfigPath));

        return $products->find_many();
    }
}