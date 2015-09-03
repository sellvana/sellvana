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
 * Class Sellvana_Sales_Admin_Dashboard_OrdersList
 *
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_Sales_Admin_Dashboard_OrdersList extends Sellvana_Sales_Admin_Dashboard_Abstract
{
    static protected $_origClass = __CLASS__;
    protected        $_modelClass = 'Sellvana_Sales_Model_Order';

    /**
     * @return array
     */
    public function getData()
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
}