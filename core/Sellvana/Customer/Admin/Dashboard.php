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
 * Class Sellvana_Customer_Admin_Dashboard
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_Customer_Admin_Dashboard extends FCom_Admin_Widget
{
    static protected $_origClass      = __CLASS__;
    protected        $_modelClass     = 'Sellvana_Customer_Model_Customer';

    /**
     * @return array
     */
    public function getCustomerRecent()
    {
        $dayLimit = $this->BConfig->get('modules/Sellvana_Customer/recent_day');

        $orm = $this->{$this->_modelClass}->orm()
            ->select(['id' , 'email', 'firstname', 'lastname', 'create_at', 'status'])
            ->order_by_desc('create_at');
        if ($dayLimit) {
            $recent = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - $dayLimit * 86400);
            $orm->where_gte('create_at', $recent);
        }
        return $orm->find_many();
    }
}