<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_ProductReviews_Model_ReviewFlag
 *
 * @property int $id
 * @property int $review_id
 * @property int $customer_id
 * @property int $helpful
 * @property int $offensive
 */
class Sellvana_ProductReviews_Model_ReviewFlag extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_review_flag';
}
