<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Model_Group extends FCom_Core_Model_Abstract
{
    protected static $_table = "fcom_customer_groups";
    protected static $_origClass = __CLASS__;

    protected static $_validationRules = [
        ['title', '@required'],
        ['code', '@required'],
    ];

    /**
     * @param bool  $new
     * @param array $args
     * @return FCom_CustomerGroups_Model_Group
     */
    public static function i($new = false, array $args = [])
    {
        return parent::i($new, $args); // auto completion helper
    }

    /**
     * Get groups in format suitable for select drop down list
     * @return array
     */
    public static function groupsOptions()
    {
        $groupModels = static::orm()->find_many();
        $groups = [];
        foreach ($groupModels as $model) {
            $key = $model->id;
            $groups[$key] = $model->title;
        }

        return $groups;
    }
}
