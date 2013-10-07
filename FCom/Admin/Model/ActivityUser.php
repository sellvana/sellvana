<?php

/**
 * @property int id
 * @property int activity_id
 * @property int user_id
 * @property enum alert_status (new, read, dismissed)
 */
class FCom_Admin_Model_ActivityUser extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_admin_activity_user';
    static protected $_origClass = __CLASS__;

}