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
 * Class Sellvana_Sales_Admin_Dashboard_OrdersTotals
 *
 * @property Sellvana_Sales_Model_StateCustom $Sellvana_Sales_Model_StateCustom
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 */
class Sellvana_Sales_Admin_Dashboard_OrdersTotals extends Sellvana_Sales_Admin_Dashboard_Abstract
{
    static protected $_origClass = __CLASS__;
    protected        $_modelClass = 'Sellvana_Sales_Model_StateCustom';

    /**
     * @return array
     */
    public function getData()
    {
        $orm = $this->Sellvana_Sales_Model_StateCustom->orm('s')
            ->left_outer_join($this->Sellvana_Sales_Model_Order->table(), ['o.state_custom', '=', 's.state_code'], 'o')
            ->group_by('s.id')
            ->select_expr('COUNT(o.id)', 'order')
            ->where('s.entity_type', 'order')
            ->select(['s.id', 's.state_label']);

        $this->_processFilters($orm);

        return $orm->find_many();
    }
}