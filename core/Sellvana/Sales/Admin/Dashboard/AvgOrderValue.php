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
 * Class Sellvana_Sales_Admin_Dashboard_AvgOrderValue
 *
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 */
class Sellvana_Sales_Admin_Dashboard_AvgOrderValue extends Sellvana_Sales_Admin_Dashboard_Abstract
{
    static protected $_origClass = __CLASS__;
    protected        $_modelClass = 'Sellvana_Sales_Model_Order';

    /**
     * @return array
     */
    public function getData()
    {
        $orm = $this->{$this->_modelClass}->orm('o')
            ->select_expr('AVG(o.grand_total)', 'avg_total');

        $this->_processFilters($orm);

        $result = (float)$orm->find_one()->get('avg_total');
        return number_format($result, 2);
    }
}