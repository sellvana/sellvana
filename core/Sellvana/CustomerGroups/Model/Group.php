<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CustomerGroups_Model_Group
 *
 * @property int $id
 * @property string $title
 * @property string $code
 */
class Sellvana_CustomerGroups_Model_Group extends FCom_Core_Model_Abstract
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
        /** @var Sellvana_CustomerGroups_Model_Group[] $groupModels */
        $groupModels = $this->orm()->find_many();
        $groups = [];
        foreach ($groupModels as $model) {
            $key = $model->id();
            $groups[$key] = $model->get('title');
        }

        return $groups;
    }

    /**
     * Get groups in format suitable for select drop down list
     * use group code instead of id
     *
     * @return array
     */
    public function groupsOptionsByCode()
    {
        /** @var Sellvana_CustomerGroups_Model_Group[] $groupModels */
        $groupModels = $this->orm()->find_many();
        $groups = [];
        foreach ($groupModels as $model) {
            $key = $model->get('code');
            $groups[$key] = $model->get('title');
        }

        return $groups;
    }

    public function notLoggedInId()
    {
        $group = $this->load('guest', 'code');
        if($group){
            return $group->id();
        }
        return null;
    }
}
