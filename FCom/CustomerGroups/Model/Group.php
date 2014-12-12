<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_CustomerGroups_Model_Group
 *
 * @property int $id
 * @property string $title
 * @property string $code
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
        /** @var FCom_CustomerGroups_Model_Group[] $groupModels */
        $groupModels = $this->orm()->find_many();
        $groups = [];
        foreach ($groupModels as $model) {
            $key = $model->id;
            $groups[$key] = $model->title;
        }

        return $groups;
    }
}
