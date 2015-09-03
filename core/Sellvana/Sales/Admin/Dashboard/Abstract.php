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
 * Class Sellvana_Sales_Admin_Dashboard_Abstract
 */
abstract class Sellvana_Sales_Admin_Dashboard_Abstract extends FCom_Admin_Widget
{
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
}