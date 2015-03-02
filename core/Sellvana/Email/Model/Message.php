<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Email_Model_Message
 *
 * @property int $id
 * @property string $recipient
 * @property string $subject
 * @property string $body
 * @property string $status
 * @property string $error_message
 * @property int $num_attempts
 * @property string $data_serialized
 * @property string $create_at
 * @property string $resent_at
 *
 * @property array $data //todo: ??????
 */
class Sellvana_Email_Model_Message extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_email_message';
    static protected $_origClass = __CLASS__;

    public function onAfterLoad()
    {
        parent::onAfterLoad();

        $this->data = $this->data_serialized ? $this->BUtil->fromJson($this->data_serialized) : [];
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        if (!$this->create_at) $this->create_at = $this->BDb->now();

        $this->data_serialized = $this->BUtil->toJson($this->data);

        return true;
    }
}
