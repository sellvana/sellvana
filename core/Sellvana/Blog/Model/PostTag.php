<?php

/**
 * Class Sellvana_Blog_Model_PostTag
 *
 * @property int $id
 * @property int $tag_id
 * @property int $post_id
 */
class Sellvana_Blog_Model_PostTag extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_blog_post_tag';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['tag_id', 'post_id'],
        'related'    => ['tag_id' => 'Sellvana_Blog_Model_Tag', 'post_id' => 'Sellvana_Blog_Model_Post.id'],
    ];
}
