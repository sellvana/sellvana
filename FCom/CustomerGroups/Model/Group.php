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
     * Get groups in format suitable for select drop down list
     * @return array
     */
    public function groupsOptions()
    {
        $groupModels = $this->orm()->find_many();
        $groups = [];
        foreach ($groupModels as $model) {
            $key = $model->id;
            $groups[$key] = $model->title;
        }

        return $groups;
    }
}
