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
 * Class Sellvana_ProductReviews_Admin_Dashboard
 *
 * @property Sellvana_ProductReviews_Model_Review $Sellvana_ProductReviews_Model_Review
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_ProductReviews_Admin_Dashboard extends FCom_Admin_Widget
{
    static protected $_origClass = __CLASS__;
    protected        $_modelClass = 'Sellvana_ProductReviews_Model_Review';

    /**
     * @return array
     */
    public function getLatestProductReviews()
    {
        $limitConfigPath = 'modules/Sellvana_ProductReviews/latest-product-reviews-limit';
        /** @var BORM $orm */
        $orm = $this->{$this->_modelClass}->orm('pr')
            ->join($this->Sellvana_Catalog_Model_Product->table(), ['p.id', '=', 'pr.product_id'], 'p')
            ->left_outer_join($this->Sellvana_Customer_Model_Customer->table(), ['c.id', '=', 'pr.customer_id'], 'c')
            ->select([
                'c.firstname',
                'c.lastname',
                'p.product_name',
                'pr.rating'
            ])
            ->order_by_desc('pr.create_at')
            ->limit($this->getLimit($limitConfigPath));

        return $orm->find_many();
    }
}