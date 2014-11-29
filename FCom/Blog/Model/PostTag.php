<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Blog_Model_PostTag
 *
 * @property int $id
 * @property int $tag_id
 * @property int $post_id
 */
class FCom_Blog_Model_PostTag extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_blog_post_tag';
    static protected $_origClass = __CLASS__;
}
