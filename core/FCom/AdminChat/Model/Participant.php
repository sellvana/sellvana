<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_AdminChat_Model_Participant
 *
 * @property int $id
 * @property int $chat_id
 * @property int $user_id
 * @property string $status
 * @property string $chat_title
 * @property string $create_at when user joined the session
 * @property string $update_at the last time user got updated on chat
 */
class FCom_AdminChat_Model_Participant extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_adminchat_participant';
    static protected $_origClass = __CLASS__;

}
