<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Cms_Model_BlockHistory
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
class Sellvana_Cms_Model_BlockHistory extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_block_history';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['block_id', 'version'],
        'related'    => [
            'block_id' => 'Sellvana_Cms_Model_Block.id',
            'user_id'  => 'FCom_Admin_Model_User.id'
        ],
    ];
}
