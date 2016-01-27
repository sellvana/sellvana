<?php

/**
 * Class FCom_Admin_Model_Personalize
 *
 * @property int $id
 * @property int $user_id
 * @property string $data_json
 */
class FCom_Admin_Model_Personalize extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_admin_personalize';
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['user_id'],
        'related'    => [
            'user_id' => 'FCom_Admin_Model_User.id'
        ],
    ];
}
