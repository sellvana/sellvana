<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Cms_Model_BlockHistory
 *
 * @property int $id
 * @property int $block_id
 * @property int $version
 * @property int $user_id
 * @property string $username
 * @property string $data
 * @property string $comments
 * @property string $ts
 */
class FCom_Cms_Model_BlockHistory extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_block_history';
    protected static $_origClass = __CLASS__;
}