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
 * Class Sellvana_Email_Admin_Dashboard
 *
 * @property Sellvana_Email_Model_Pref $Sellvana_Email_Model_Pref
 */
class Sellvana_Email_Admin_Dashboard extends FCom_Admin_Widget
{
    static protected $_origClass      = __CLASS__;
    protected        $_modelClass     = 'Sellvana_Email_Model_Pref';

    public           $limitConfigPath = 'modules/Sellvana_Email/latest_new_limit';

    /**
     * @return array
     */
    public function getLatestNewsletterSubscriptions(){

        $orm = $this->{$this->_modelClass}->orm('p')
            ->select([
                'p.email'
            ])
            ->where('p.sub_newsletter', '1')
            ->order_by_desc('p.create_at')
            ->limit($this->getLimit());

        return $orm->find_many();
    }
}