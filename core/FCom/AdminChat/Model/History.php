<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_AdminChat_Model_History
 *
 * @property int $id
 * @property int $chat_id chat session
 * @property int $user_id who sent the message
 * @property string $entry_type
 * @property string $text
 * @property string $create_at message text
 * @property string $update_at message time
 */
class FCom_AdminChat_Model_History extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_adminchat_history';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['chat_id', 'user_id', 'entry_type', 'create_at'],
        'related'    => [
            'chat_id' => 'FCom_AdminChat_Model_Chat.id',
            'user_id' => 'FCom_Admin_Model_User.id'
        ],
    ];
}
