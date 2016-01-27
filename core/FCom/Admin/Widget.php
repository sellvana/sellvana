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
 * @since 0.5.1.0
 */

/**
 * Base class for all Admin Dashboard widgets
 */
abstract class FCom_Admin_Widget extends BClass
{
    const            DEFAULT_LIMIT_CONFIG_PATH = 'modules/FCom_Admin/default_dashboard_widget_limit';

    static protected $_defaultLimit;
    protected        $_limit = [];

    /**
     * Get row limit from the config
     * @return int
     */
    public function getLimit($key = self::DEFAULT_LIMIT_CONFIG_PATH)
    {

        if (array_key_exists($key, $this->_limit)) {
            return $this->_limit[$key];
        }

        $limit = $this->BConfig->get($key);

        if (null === $limit) {
            if (null === self::$_defaultLimit) {
                self::$_defaultLimit = $this->BConfig->get(self::DEFAULT_LIMIT_CONFIG_PATH);
            }

            $limit = self::$_defaultLimit;
        }

        $this->_limit[$key] = $limit;

        return $this->_limit[$key];
    }
}