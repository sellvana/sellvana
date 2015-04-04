<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Blog_Model_PostCategory
 *
 * @property int $id
 * @property int $category_id
 * @property int $post_id
 */
class Sellvana_Blog_Model_PostCategory extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_blog_post_category';

}